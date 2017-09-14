<?php

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

function ox_echo($message, $color = 'white')
{
    if ($message) {
        $output = new ConsoleOutput();
        $output->writeln('<fg=' . $color .'>' . $message . '</>');
    }
}

function ox_echo_info($message)
{
    ox_echo($message, 'blue');
}

function ox_echo_success($message)
{
    ox_echo($message, 'green');
}

function ox_echo_error($message)
{
    ox_echo($message, 'red');
}

function ox_exec($command)
{
    $process = new Process($command);
    try {
        $process->mustRun();
        ox_echo_info($process->getOutput());
    } catch (ProcessFailedException $e) {
        ox_echo_error($e->getMessage());
        return false;
    }
    return true;
}

function ox_mkdir($dir)
{
    $fs = new Filesystem();
    try {
        $fs->mkdir($dir, 0755);
    } catch (IOExceptionInterface $e) {
        echo $e;
        return false;
    }
    return true;
}

function ox_chown($dir, $owner, $group)
{
    $fs = new Filesystem();
    try {
        $fs->chown($dir, $owner, true);
        $fs->chgrp($dir, $group, true);
    } catch (IOExceptionInterface $e) {
        echo $e;
        return false;
    }
    return true;
}

function ox_template($template, $data = null)
{
    $m = new Mustache_Engine(['loader' => new Mustache_Loader_FilesystemLoader(OX_ROOT . '/templates')]);
    return $m->render($template, $data);
}
