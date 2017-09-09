<?php
namespace Ox;

use Pimple\Container;

class App extends Container
{
    public function __construct()
    {
        parent::__construct();
        $app = $this;
    }
}
