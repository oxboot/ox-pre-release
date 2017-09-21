<?php
namespace Ox\Command;

use Ox\Stack\WPCLI;
use Ox\Utils;
use Ox\Stack\PHP;
use Ox\Stack\MySQL;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class SiteCreateCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('site:create')
            ->setDescription('Create a new site')
            ->setHelp('This command allows you to create a new site')
            ->addArgument('site_name', InputArgument::REQUIRED, 'Name of the site')
            ->addOption('mysql', null, InputOption::VALUE_NONE, 'MySQL support')
            ->addOption('package', null, InputOption::VALUE_OPTIONAL, 'Name of the package to install')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs = $this->app['filesystem'];
        $config = $this->app['config'];
        $site_name = $input->getArgument('site_name');
        $site_dir = '/var/www/'.$site_name;
        $site_webdir = $site_dir.DS.$config->get('main.public');
        $mysql_support = $input->getOption('mysql');
        $package = $input->getOption('package');
        $stack = [];
        $stack_file = OX_DB_FOLDER.'stack.yml';
        $site_file = OX_DB_FOLDER.'/sites/'.$site_name.'.yml';

        if (file_exists($stack_file)) {
            try {
                $stack = Yaml::parse(file_get_contents($stack_file));
            } catch (ParseException $e) {
                ox_echo_error('Unable to parse Ox stack config: ' . $e);
                return false;
            }
        } else {
            try {
                $fs->dumpFile($stack_file, '');
            } catch (ParseException $e) {
                ox_echo_error('Unable to create Ox stack config: ' . $e);
                return false;
            }
        }

        ox_echo_info('Try to create site '.$site_name);

        if ($fs->exists($site_file)) {
            ox_echo_error('Site '.$site_name.' config already exists');
            return false;
        }

        if ($fs->exists($site_dir)) {
            ox_echo_error('Site '.$site_name.' folder already exists');
            return false;
        }

        ox_mkdir($site_webdir);
        $fs->dumpFile($config->get('nginx.sites-available').DS.$site_name, ox_template('stack/nginx/site', ['site_name' => $input->getArgument('site_name')]));
        $fs->symlink($config->get('nginx.sites-available').DS.$site_name, $config->get('nginx.sites-enabled').DS.$site_name);
        if (!ox_exec('nginx -t') || !$fs->exists([$site_dir, '/etc/nginx/sites-available/' . $site_name, '/etc/nginx/sites-enabled/' . $site_name])) {
            $fs->remove([$site_dir, $config->get('nginx.sites-available').DS.$site_name, $config->get('nginx.sites-enabled').DS.$site_name]);
            ox_echo_error('Site '.$site_name.' not created, error occurred');
            return false;
        }
        ox_chown($site_dir, 'www-data', 'www-data');
        ox_exec('service nginx restart');

        if (!$stack['php']) {
            $stack['php'] = PHP::install();
            try {
                $fs->dumpFile($stack_file, Yaml::dump($stack));
            } catch (ParseException $e) {
                ox_echo_error('Unable to write Ox stack config: '.$e);
                return false;
            }
        }
        if ($package) {
            if (file_exists(OX_ROOT . '/packages/').$package.'.yml') {
                ox_echo('Package '.$package.' exists');
                $package_config = Yaml::parse(file_get_contents(OX_ROOT . '/packages/'.$package.'.yml'));
                if (in_array('mysql', $package_config['dependencies'])) {
                    $mysql_support = true;
                }
                if (in_array('wp-cli', $package_config['dependencies'])) {
                    WPCLI::install();
                }
            }
        }
        if ($mysql_support) {
            if (!isset($stack['mysql'])) {
                $stack['mysql'] = MySQL::install();
                try {
                    $fs->dumpFile($stack_file, Yaml::dump($stack));
                } catch (ParseException $e) {
                    ox_echo_error('Unable to write Ox stack config: '.$e);
                    return false;
                }
            }
            $mysql_site_user = str_replace('.', '', $site_name).'_user_'.Utils::randomString(8);
            $mysql_site_password = Utils::randomString(8);
            $mysql_site_db = str_replace('.', '', $site_name).'_db_'.Utils::randomString(8);
            MySQL::createDb($mysql_site_db);
            MySQL::createUser($mysql_site_user, $mysql_site_password);
            MySQL::grantDbUser($mysql_site_db, $mysql_site_user);
            $site['db_name'] = $mysql_site_db;
            $site['db_user'] = $mysql_site_user;
            $site['db_pass'] = $mysql_site_password;
            try {
                $fs->dumpFile($site_file, Yaml::dump($site));
            } catch (ParseException $e) {
                ox_echo_error('Unable to write site '.$site_name.' config: '.$e);
                return false;
            }
        }

        if ($package) {
            if (file_exists(OX_ROOT . '/packages/').$package.'.yml') {
                $package_config = Yaml::parse(file_get_contents(OX_ROOT . '/packages/'.$package.'.yml'));
                if (isset($package_config['commands'])) {
                    foreach ($package_config['commands'] as $command) {
                        ox_exec(ox_mustache($command, [
                            'site_webdir' => $site_webdir,
                            'db_name' => $site['db_name'],
                            'db_user' => $site['db_user'],
                            'db_pass' => $site['db_pass'],
                            'title' => $site_name,
                            'url' => 'http://'.$site_name,
                            'admin_user' => 'admin',
                            'admin_email' => 'no-reply@'.$site_name
                        ]), 'www-data');
                    }
                }
            }
        }

        ox_echo_success('Site '.$site_name.' created successful');
        return true;
    }
}
