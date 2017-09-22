<?php
namespace Ox;

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

        try {
            $config_file = OX_CONFIG_FOLDER . 'ox.ini';
            if (!$app['filesystem']->exists($config_file)) {
                $app['filesystem']->dumpFile($config_file, ox_template('config/ox'));
            }
            $app['config'] = Config::load(OX_CONFIG_FOLDER . 'ox.ini');
        } catch (Exception $e) {
            ox_echo_error('Error loading Ox config: ' . $e);
        }
    }
}
