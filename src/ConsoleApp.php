<?php
namespace Ox;

use Symfony\Component\Console\Application as SymfonyConsoleApplication;
use Ox\Command\SiteCreateCommand;
use Ox\Command\SiteDeleteCommand;

class ConsoleApp extends SymfonyConsoleApplication
{
    private $app;

    public function getApp()
    {
        return $this->app;
    }

    public function __construct(App $app)
    {
        $this->app = $app;
        parent::__construct('ox', OX_VERSION);

        $this->add(new SiteCreateCommand());
        $this->add(new SiteDeleteCommand());
    }
}
