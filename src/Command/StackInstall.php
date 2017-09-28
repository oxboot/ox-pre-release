<?php

namespace Ox\Command;

use Ox\Stack\StackManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StackInstall extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('stack:install')
            ->setDescription('Install stack component')
            ->setHelp('This command allows you to install stack component')
            ->addArgument('stack_component', InputArgument::REQUIRED, 'Stack component')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * Arguments from user input
         */
        $stack_component = new StackManager($input->getArgument('stack_component'));

        if ($stack_component->checkInstall()) {
            return false;
        }
        $stack_component->install();
        return true;
    }
}
