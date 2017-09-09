<?php
namespace Ox\Command;

use Ox\Utils;
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
            ->addOption('php', null, InputOption::VALUE_NONE, 'PHP support')
            ->addOption('mysql', null, InputOption::VALUE_NONE, 'MySQL support')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $site_name = $input->getArgument('site_name');
        $php_support = $input->getOption('php');
        $mysql_support = $input->getOption('mysql');

        $stack_file = OX_DB_FOLDER . 'stack.yml';
        if (file_exists($stack_file)) {
            try {
                $stack = Yaml::parse(file_get_contents($stack_file));
            } catch (ParseException $e) {
                ox_echo_error('Unable to parse Ox stack config: ' . $e);
                exit;
            }
        } else {
            try {
                $this->app['filesystem']->dumpFile($stack_file, '');
            } catch (ParseException $e) {
                ox_echo_error('Unable to write Ox stack config: ' . $e);
                exit;
            }
        }
        if ($php_support && !$stack['php']) {
            ox_echo_info('Installing PHP 7.1, please wait...');
            ox_exec("add-apt-repository -y 'ppa:ondrej/php'");
            ox_exec('apt-get update &>> /dev/null');
            ox_exec('apt-get -y install ' . $this->app['config']->get('apt.php71'));
            $stack['php'] = true;
            try {
                $this->app['filesystem']->dumpFile($stack_file, Yaml::dump($stack));
            } catch (ParseException $e) {
                ox_echo_error('Unable to write Ox stack config: ' . $e);
                exit;
            }
        }
        if ($mysql_support && !$stack['mysql']) {
            ox_echo_info('Installing MariaDB 10.2, please wait...');
            ox_exec('apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xF1656F24C74CD1D8');
            ox_exec("add-apt-repository 'deb [arch=amd64,i386,ppc64el] http://ams2.mirrors.digitalocean.com/mariadb/repo/10.2/ubuntu xenial main'");
            ox_exec('apt-get update &>> /dev/null');
            $mysql_password = Utils::randomString(8);
            $mysql_config = "[client] \n user = root\n password = " . $mysql_password;
            ox_exec("echo \"mariadb-server mysql-server/root_password password " . $mysql_password . "\"" . " | debconf-set-selections");
            ox_exec("echo \"mariadb-server mysql-server/root_password_again password " . $mysql_password . "\"" . " | debconf-set-selections");
            ox_echo_info('Writting MySQL configuration...');
            $conf_path = '/etc/mysql/conf.d/my.cnf';
            $this->app['filesystem']->dumpFile($conf_path, $mysql_config);
            ox_exec('apt-get -y install mariadb-server');
            $stack['mysql'] = true;
            try {
                $this->app['filesystem']->dumpFile($stack_file, Yaml::dump($stack));
            } catch (ParseException $e) {
                ox_echo_error('Unable to write Ox stack config: ' . $e);
                exit;
            }
        }
    }
}
