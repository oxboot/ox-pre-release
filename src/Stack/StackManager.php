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
    private $stack_config;
    private $stack_component_name;
    private $stack_component_class;
    private $utils;
    private $filesystem;

    public function __construct($stack_component_name)
    {
        $this->utils = new Utils();
        $this->filesystem = new Filesystem();
        $this->stack = new Db('stack');
        $this->stack_config = $this->stack->read();

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
        if (in_array($this->stack_component_name, $this->stack_config)) {
            return true;
        }
        return false;
    }

    public function install()
    {
        $stack_component = new $this->stack_component_class;
        $install_result = $stack_component->install();
        if ($install_result) {
            if (!in_array($this->stack_component_name, $this->stack_config)) {
                $this->stack_config[] = $this->stack_component_name;
                $this->stack->write($this->stack_config);
            }
            return true;
        }
        return false;
    }

    public function uninstall()
    {
        $stack_component = new $this->stack_component_class;
        $uninstall_result = $stack_component->uninstall();
        if ($uninstall_result) {
            $this->stack_config = array_unique($this->stack_config);
            $key = array_search($this->stack_component_name, $this->stack_config);
            if (isset($key)) {
                unset($this->stack_config[$key]);
                $this->stack->write($this->stack_config);
            }
            return true;
        }
        return false;
    }

    public function reinstall()
    {
    }
}
