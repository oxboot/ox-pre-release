<?php
namespace Ox\Command;

use Ox\Stack\Component\Mysql;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Yaml\Yaml;

class SiteDeleteCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('site:delete')
            ->setDescription('Delete an existing site')
            ->setHelp('This command allows you to delete an existing site')
            ->addArgument('site_name', InputArgument::REQUIRED, 'Name of the site')
            ->addOption('no-prompt', null, InputOption::VALUE_NONE, 'No prompt option')
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

        $mysql_stack = new MySQL();

        /**
         * Arguments from user input
         */
        $site_name = $input->getArgument('site_name');
        $site_dir = '/var/www/'.$site_name;
        $site_webdir = $site_dir.'/htdocs';
        $helper = $this->getHelper('question');
        $no_prompt = $input->getOption('no-prompt');

        /**
         * Config files
         */
        $site_file = OX_DB_FOLDER.'/sites/'.$site_name.'.yml';

        $utils->echoInfo('Try to delete site '.$site_name);

        if (!$filesystem->exists($site_file)) {
            $utils->echoError('Site '.$site_name.' config not exists');
            if (!$filesystem->exists($site_dir)) {
                $utils->echoError('Site '.$site_name.' folder not exists');
                return false;
            }
        }

        if (!$no_prompt) {
            $question = new ConfirmationQuestion('Delete site '.$site_name.': Are you sure(y/N)?', false);
            if (!$helper->ask($input, $output, $question)) {
                $utils->echoError('Operation canceled');
                return false;
            }
        }

        $filesystem->remove([
            $site_dir,
            $config->get('nginx.sites-available').DS.$site_name,
            $config->get('nginx.sites-enabled').DS.$site_name
        ]);

        if (file_exists($site_file)) {
            $site_config = Yaml::parse(file_get_contents($site_file));
            try {
                $mysql_stack->deleteUser($site_config['db_user']);
                $mysql_stack->deleteDb($site_config['db_name']);
            } catch (\Exception $e) {
                $utils->echoError('Error deleting site '.$site_name.' user & database: ' . $e->getMessage());
                return false;
            }
            $filesystem->remove($site_file);
        }
        $utils->exec('service nginx restart');
        $utils->echoSuccess('Site '.$site_name.' deleted successful');
        return true;
    }
}
