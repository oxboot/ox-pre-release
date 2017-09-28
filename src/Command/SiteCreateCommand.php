<?php
namespace Ox\Command;

use Ox\App\Db;
use Ox\Stack\Component\Composer;
use Ox\Stack\Component\Php;
use Ox\Stack\Component\Mysql;
use Ox\Stack\Component\Wpcli;
use Ox\Stack\StackManager;
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

        $site_config = new Db('site');

        /**
         * Arguments from user input
         */
        $site_name = $input->getArgument('site_name');
        $package = $input->getOption('package');

        /**
         * Additional variables
         */
        $site_dir = '/var/www/'.$site_name;
        $site_webdir = $site_dir.DS.$config->get('main.public');
        $site_db_name = null;
        $site_db_user = null;
        $site_db_pass = null;

        $utils->echoInfo('Try to create site '.$site_name);

        if ($site_config->exists()) {
            $utils->echoError('Site '.$site_name.' config already exists');
            return false;
        }

        if ($filesystem->exists($site_dir)) {
            $utils->echoError('Site '.$site_name.' folder already exists');
            return false;
        }

        if ($package) {
            if ($filesystem->exists(OX_ROOT.'/packages/').$package.'.yml') {
                $utils->echoInfo('Install dependencies for package: '.$package);
                $package_config = Yaml::parse(file_get_contents(OX_ROOT . '/packages/'.$package.'.yml'));
                if (isset($package_config['dependencies'])) {
                    foreach ($package_config['dependencies'] as $dependence) {
                        $stack_manager = new StackManager($dependence);
                        if (!$stack_manager->checkRegister()) {
                            $utils->echoError('Stack component: '.$dependence.' not registered');
                            return false;
                        }
                        if (!$stack_manager->checkInstall()) {
                            $utils->echoInfo('Stack component: '.$dependence.' not installed');
                            $utils->echoInfo('Try to install stack component: '.$dependence);
                            $install_result = $stack_manager->install();
                            if (!$install_result) {
                                $utils->echoError('Error installing stack component: '.$dependence);
                                return false;
                            }
                        }
                        if ($dependence === 'mysql') {
                            $mysql_stack = new Mysql();
                            $site_db_name = str_replace('.', '', $site_name).'_db_'.$utils->randomString();
                            $site_db_user = str_replace('.', '', $site_name).'_user_'.$utils->randomString();
                            $site_db_pass = $utils->randomString();
                            $mysql_stack->createDb($site_db_name);
                            $mysql_stack->createUser($site_db_user, $site_db_pass);
                            $mysql_stack->grantDbUser($site_db_name, $site_db_user);
                        }
                    }
                }
                $utils->echoSuccess('All dependencies for package: '.$package.' installed successful');
            }
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

        $database_content = [
            'site_name' => $site_name,
            'site_dir' => $site_dir,
            'site_webdir' => $site_webdir,
            'site_db_name' => $site_db_name,
            'site_db_user' => $site_db_user,
            'site_db_pass' => $site_db_pass,
            'site_title' => $site_name,
            'site_url' => 'http://'.$site_name,
            'site_admin_user' => 'admin',
            'site_admin_email' => 'no-reply@'.$site_name
        ];

        if ($package) {
            if (file_exists(OX_ROOT . '/packages/').$package.'.yml') {
                $package_config = Yaml::parse(file_get_contents(OX_ROOT . '/packages/'.$package.'.yml'));
                if (isset($package_config['commands'])) {
                    foreach ($package_config['commands'] as $command) {
                        $command_output = $utils->exec($utils->templateString($command, $database_content), 'www-data');
                        if (!$command_output) {
                            return false;
                        }
                    }
                }
            }
        }

        $site_config->write($database_content);
        $utils->echoSuccess('Site '.$site_name.' created successful');
        return true;
    }
}
