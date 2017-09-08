#!/usr/bin/env php
<?php
define('DS', '/');
define('OX_ROOT', str_replace(DIRECTORY_SEPARATOR, DS, __DIR__ . DS));
define('OX_VERSION', '0.0.0');

require OX_ROOT . 'vendor/autoload.php';

$application = new \Symfony\Component\Console\Application('ox ' . OX_VERSION);

$application->add(new Ox\Command\SiteCreateCommand());
$application->add(new Ox\Command\SiteDeleteCommand());

$application->run();
