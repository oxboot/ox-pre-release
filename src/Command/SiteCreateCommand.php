<?php
namespace Ox\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

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
    }
}
