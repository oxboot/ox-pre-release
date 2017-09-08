<?php
namespace Ox\Command;

use Symfony\Component\Console\Command\Command;

class SiteDeleteCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('site:delete')
            ->setDescription('Delete an existing site')
            ->setHelp('This command allows you to delete an existing site')
        ;
    }
}
