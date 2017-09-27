<?php
namespace Ox;

use Pimple\Container;
use Ox\App\Config;
use Ox\App\Utils;
use Symfony\Component\Filesystem\Filesystem;

class App extends Container
{
    public function __construct()
    {
        parent::__construct();
        $app = $this;

        $app['config'] = function ($c) {
            return new Config();
        };

        $app['utils'] = function ($c) {
            return new Utils();
        };

        $app['filesystem'] = function ($c) {
            return new Filesystem();
        };
    }
}
