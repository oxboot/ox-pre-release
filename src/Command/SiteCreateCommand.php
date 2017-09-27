<?php
namespace Ox\Command;

use Ox\Stack\Composer;
use Ox\Stack\PHP;
use Ox\Stack\MySQL;
use Ox\Stack\WpCLI;
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
            ->addOption('package', null, InputOption::VALUE_OPTIONAL, 'Package to install')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var \Symfony\Component\Filesystem\Filesystem $filesystem
         * @var \Ox\App\Utils $utils
         * @var \Noodlehaus\Config $config
         */
        $filesystem = $this->app['filesystem'];
        $utils = $this->app['utils'];
        $config = $this->app['config'];

        $php_stack = new PHP();
        $mysql_stack = new MySQL();

        /**
         * Arguments from user input
         */
        $site_name = $input->getArgument('site_name');
        $site_dir = '/var/www/'.$site_name;
        $site_webdir = $site_dir.DS.$config->get('main.public');
        $package = $input->getOption('package');

        /**
         * Config files
         */
        $stack_file = OX_DB_FOLDER.'stack.yml';
        $site_file = OX_DB_FOLDER.'/sites/'.$site_name.'.yml';

        if (file_exists($stack_file)) {
            try {
                $stack = Yaml::parse(file_get_contents($stack_file));
            } catch (ParseException $e) {
                $utils->echoError('Unable to parse Ox stack config: ' . $e->getMessage());
                return false;
            }
        } else {
            try {
                $filesystem->dumpFile($stack_file, '');
            } catch (ParseException $e) {
                $utils->echoError('Unable to create Ox stack config: ' . $e->getMessage());
                return false;
            }
        }

        $utils->echoInfo('Try to create site '.$site_name);

        if ($filesystem->exists($site_file)) {
            $utils->echoError('Site '.$site_name.' config already exists');
            return false;
        }

        if ($filesystem->exists($site_dir)) {
            $utils->echoError('Site '.$site_name.' folder already exists');
            return false;
        }

        try {
            $utils->mkdir($site_webdir);
            $filesystem->dumpFile(
                $config->get('nginx.sites-available').DS.$site_name,
                $utils->templateFile('stack/nginx/site', ['site_name' => $input->getArgument('site_name')])
            );
            $filesystem->symlink(
                $config->get('nginx.sites-available').DS.$site_name,
                $config->get('nginx.sites-enabled').DS.$site_name
            );

            $utils->chown($site_dir, 'www-data', 'www-data');
            $utils->exec('service nginx restart');
        } catch (ParseException $e) {
            $utils->echoError('Site '.$site_name.' not created, error occurred: '.$e->getMessage());
            return false;
        }

        if (!isset($stack['php'])) {
            $stack['php'] = $php_stack->install();
            try {
                $filesystem->dumpFile($stack_file, Yaml::dump($stack));
            } catch (ParseException $e) {
                $utils->echoError('Unable to write Ox stack config: '.$e->getMessage());
                return false;
            }
        }
        if ($package) {
            if (file_exists(OX_ROOT . '/packages/').$package.'.yml') {
                $utils->echoInfo('Try to install package: '.$package);
                $package_config = Yaml::parse(file_get_contents(OX_ROOT . '/packages/'.$package.'.yml'));
                if (isset($package_config['dependencies'])) {
                    if (in_array('mysql', $package_config['dependencies'])) {
                        if (!isset($stack['mysql'])) {
                            $stack['mysql'] = $mysql_stack->install();
                            try {
                                $filesystem->dumpFile($stack_file, Yaml::dump($stack));
                            } catch (ParseException $e) {
                                $utils->echoError('Unable to write Ox stack config: '.$e->getMessage());
                                return false;
                            }
                        }
                        $mysql_site_user = str_replace('.', '', $site_name).'_user_'.$utils->randomString();
                        $mysql_site_password = $utils->randomString();
                        $mysql_site_db = str_replace('.', '', $site_name).'_db_'.$utils->randomString();
                        $mysql_stack->createDb($mysql_site_db);
                        $mysql_stack->createUser($mysql_site_user, $mysql_site_password);
                        $mysql_stack->grantDbUser($mysql_site_db, $mysql_site_user);
                        $site['db_name'] = $mysql_site_db;
                        $site['db_user'] = $mysql_site_user;
                        $site['db_pass'] = $mysql_site_password;
                        try {
                            $filesystem->dumpFile($site_file, Yaml::dump($site));
                        } catch (ParseException $e) {
                            $utils->echoError('Unable to write site '.$site_name.' config: '.$e->getMessage());
                            return false;
                        }
                    }
                    if (in_array('composer', $package_config['dependencies'])) {
                        (new Composer)->install();
                    }
                    if (in_array('wp-cli', $package_config['dependencies'])) {
                        (new WpCLI)->install();
                    }
                }
            }
        }

        if ($package) {
            if (file_exists(OX_ROOT . '/packages/').$package.'.yml') {
                $package_config = Yaml::parse(file_get_contents(OX_ROOT . '/packages/'.$package.'.yml'));
                if (isset($package_config['commands'])) {
                    foreach ($package_config['commands'] as $command) {
                        $command_output = $utils->exec($utils->templateString($command, [
                            'site_dir' => $site_dir,
                            'site_webdir' => $site_webdir,
                            'db_name' => isset($site['db_name']) ? $site['db_name'] : '',
                            'db_user' => isset($site['db_user']) ? $site['db_user'] : '',
                            'db_pass' => isset($site['db_pass']) ? $site['db_pass'] : '',
                            'site_title' => $site_name,
                            'site_url' => 'http://'.$site_name,
                            'site_admin_user' => 'admin',
                            'site_admin_email' => 'no-reply@'.$site_name
                        ]), 'www-data');
                        if (!$command_output) {
                            return false;
                        }
                    }
                }
            }
        }

        $utils->echoInfo('Site '.$site_name.' created successful');
        return true;
    }
}
