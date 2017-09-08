<?php
namespace Ox\Command;

use Symfony\Component\Console\Command\Command;

class SiteCreateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('site:create')
            ->setDescription('Create a new site')
            ->setHelp('This command allows you to create a new site')
        ;
    }
}
