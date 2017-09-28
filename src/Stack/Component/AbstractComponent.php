<?php
namespace Ox\Stack\Component;

use Ox\App\Utils;
use Symfony\Component\Filesystem\Filesystem;
use Noodlehaus\Config;
use Noodlehaus\Exception;

abstract class AbstractComponent
{
    protected $utils;
    protected $filesystem;
    protected $config;

    public function __construct()
    {
        $this->utils = new Utils();
        $this->filesystem = new Filesystem();
        try {
            $this->config = new Config(OX_CONFIG_FOLDER.'/ox.ini');
        } catch (Exception $e) {
            die('Error loading Ox config: ' . $e->getMessage());
        }
    }

    abstract public function install();

    abstract public function uninstall();
}
