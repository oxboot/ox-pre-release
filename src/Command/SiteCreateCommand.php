<?php
namespace Ox\Command;

use Ox\MySQL;
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

        $stack_file = OX_DB_FOLDER . 'stack.yml';
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
        if ($fs->exists($site_dir)) {
            ox_echo_error('Site '.$site_name.' already exists');
            return false;
        }
        ox_mkdir($site_webdir);
        $fs->dumpFile($config->get('nginx.sites-available').DS.$site_name, ox_template('nginx/site', ['site_name' => $input->getArgument('site_name')]));
        $fs->symlink($config->get('nginx.sites-available').DS.$site_name, $config->get('nginx.sites-enabled').DS.$site_name);
        $fs->dumpFile($site_webdir.'/index.php', ox_template('php/default', ['site_name' => $site_name]));
        if (!ox_exec('nginx -t') || !$fs->exists([$site_dir, '/etc/nginx/sites-available/' . $site_name, '/etc/nginx/sites-enabled/' . $site_name])) {
            $fs->remove([$site_dir, $config->get('nginx.sites-available').DS.$site_name, $config->get('nginx.sites-enabled').DS.$site_name]);
            ox_echo_error('Site '.$site_name.' not created, error occurred');
            return false;
        }
        ox_chown($site_dir, 'www-data', 'www-data');
        ox_exec('service nginx restart');
        if ($mysql_support && !$stack['mysql']) {
            $stack['mysql'] = MySQL::install();
            if ($stack['mysql']) {
                try {
                    $fs->dumpFile($stack_file, Yaml::dump($stack));
                } catch (ParseException $e) {
                    ox_echo_error('Unable to write Ox stack config: '.$e);
                    return false;
                }
            }
        }
        ox_echo_success('Site '.$site_name.' created successful');
        return true;
    }
}
