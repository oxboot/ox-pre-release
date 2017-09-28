<?php

namespace Ox\Command;

use Ox\Stack\StackManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StackUninstall extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('stack:uninstall')
            ->setDescription('Delete an existing site')
            ->setHelp('This command allows you to delete an existing site')
            ->addArgument('stack_component', InputArgument::REQUIRED, 'Stack component')
            ->addOption('no-prompt', null, InputOption::VALUE_NONE, 'No prompt option')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /**
         * Arguments from user input
         */
        $stack_component = new StackManager($input->getArgument('stack_component'));
        $no_prompt = $input->getOption('no-prompt');

        if (!$stack_component->checkInstall()) {
            return false;
        }
        $stack_component->uninstall();
        return true;
    }
}
