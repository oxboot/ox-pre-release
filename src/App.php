<?php
namespace Ox;

use Pimple\Container;
use Noodlehaus\Config;
use Noodlehaus\ErrorException;
use Symfony\Component\Filesystem\Filesystem;

class App extends Container
{
    public function __construct()
    {
        parent::__construct();
        $app = $this;

        try {
            $app['config'] = Config::load(OX_CONFIG_FOLDER . 'ox.ini');
        } catch (ErrorException $e) {
            ox_echo_error('Error loading Ox config: ' . $e);
        }

        $app['filesystem'] = $app->factory(function ($c) {
            return new Filesystem();
        });
    }
}
