<?php
namespace Ox\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;

class SiteDeleteCommand extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('site:delete')
            ->setDescription('Delete an existing site')
            ->setHelp('This command allows you to delete an existing site')
            ->addArgument('site_name', InputArgument::REQUIRED, 'Name of the site')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fs = $this->app['filesystem'];
        $config = $this->app['config'];
        $site_name = $input->getArgument('site_name');
        $site_dir = '/var/www/'.$site_name;
        $site_webdir = $site_dir . '/htdocs';
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Delete site: Are you sure(y/N)?', false);
        if (!$helper->ask($input, $output, $question)) {
            ox_echo_success('Operation canceled');
            return false;
        }
        ox_echo_info('Try to delete site '.$site_name);
        if (!$fs->exists($site_dir)) {
            ox_echo_error('Site '.$site_name.' not exists');
            return false;
        }
        $fs->remove([$site_dir, $config->get('nginx.sites-available').DS.$site_name, $config->get('nginx.sites-enabled').DS.$site_name]);
        ox_exec('service nginx restart');
        ox_echo_success('Site '.$site_name.' deleted successful');
        return true;
    }
}
