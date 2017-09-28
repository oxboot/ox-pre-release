<?php

namespace Ox\Stack;

use Ox\App\Db;
use Ox\App\Utils;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class StackManager
{
    private $stack;
    private $stack_component_name;
    private $stack_component_class;
    private $utils;
    private $filesystem;

    public function __construct($stack_component_name)
    {
        $this->utils = new Utils();
        $this->filesystem = new Filesystem();
        $this->stack = new Db('stack');

        $this->stack_component_name = $stack_component_name;
        $this->stack_component_class = '\\Ox\\Stack\\Component\\'.ucfirst($stack_component_name);
    }

    public function checkRegister()
    {
        if (class_exists($this->stack_component_class)) {
            return true;
        }
        return false;
    }

    public function checkInstall()
    {
        if (isset($stack) && is_array($stack)) {
            if (in_array($this->stack_component_name, $stack)) {
                return true;
            }
        }
        return false;
    }

    public function install()
    {
        $stack_component = new $this->stack_component_class;
        $install_result = $stack_component->install();
        if (!$install_result) {

        }

    }

    public function uninstall()
    {
    }

    public function reinstall()
    {
    }
}
