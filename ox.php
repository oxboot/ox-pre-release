#!/usr/bin/env php
<?php
define('DS', '/');
define('OX_ROOT', str_replace(DIRECTORY_SEPARATOR, DS, __DIR__ . DS));
define('OX_VERSION', '0.0.0');
define('OX_CONFIG_FOLDER', '/etc/ox/');

require OX_ROOT . 'vendor/autoload.php';

$app = new \Ox\App();
$console = new \Ox\ConsoleApp($app);

$console->run();
