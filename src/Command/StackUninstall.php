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
            ->setHelp('This command allows you to uninstall stack component')
            ->addArgument('stack_component', InputArgument::REQUIRED, 'Stack component')
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

        /**
         * Arguments from user input
         */
        $stack_component = $input->getArgument('stack_component');
        $stack_component_class = new StackManager($stack_component);
        $no_prompt = $input->getOption('no-prompt');

        if (!$stack_component_class->checkRegister()) {
            $utils->echoError('Stack component: '.$stack_component.' not registered');
            return false;
        }

        if (!$stack_component_class->checkInstall()) {
            $utils->echoError('Stack component: '.$stack_component.' already uninstalled');
            return false;
        }
        $stack_component_class->uninstall();
        return true;
    }
}
