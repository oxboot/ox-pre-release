<?php
namespace Ox\App;

use Symfony\Component\Filesystem\Filesystem;

class Config extends \Noodlehaus\Config
{
    public $config_file = '/etc/ox/ox.ini';

    public function __construct()
    {
        $utils = new Utils();
        $filesystem = new Filesystem();

        if (!$filesystem->exists($this->config_file)) {
            try {
                $filesystem->dumpFile($this->config_file, $utils->templateFile('config/ox'));
            } catch (\Exception $e) {
                die('Error creating Ox config file: '.$e->getMessage());
            }
        }
        parent::__construct($this->config_file);
    }
}
