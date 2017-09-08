#!/usr/bin/env php
<?php
define('OX_DIR', __DIR__);

require OX_DIR . '/vendor/autoload.php';

$application = new \Symfony\Component\Console\Application();

$application->add(new Ox\Command\SiteCreateCommand());
$application->add(new Ox\Command\SiteDeleteCommand());

$application->run();
