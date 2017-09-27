<?php
namespace Ox;

use Ox\App\Utils;
use Pimple\Container;
use Symfony\Component\Filesystem\Filesystem;
use Noodlehaus\Config;
use Noodlehaus\Exception;

class App extends Container
{
    public function __construct()
    {
        parent::__construct();
        $app = $this;

        $app['filesystem'] = $app->factory(function ($c) {
            return new Filesystem();
        });

        $app['utils'] = $app->factory(function ($c) {
            return new Utils();
        });

        try {
            $app['config'] = new Config(OX_CONFIG_FOLDER . 'ox.ini');
        } catch (Exception $e) {
            die('Error loading Ox config: ' . $e->getMessage());
        }
    }
}
