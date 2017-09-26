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

function ox_distro_name()
{
    $process = new Process('lsb_release -is');
    try {
        $process->run();
    } catch (ProcessFailedException $e) {
        ox_echo_error($e->getMessage());
        return false;
    }
    return trim($process->getOutput());
}

function ox_distro_version()
{
    $process = new Process('lsb_release -rs');
    try {
        $process->run();
    } catch (ProcessFailedException $e) {
        ox_echo_error($e->getMessage());
        return false;
    }
    return trim($process->getOutput());
}

function ox_exec($command, $user = null)
{
    if (isset($user)) {
        $command = "su ".$user." -c \"".$command."\"";
    }
    $process = new Process($command);
    try {
        $process->setTimeout(3600);
        $process->mustRun(function ($type, $buffer) {
            ox_echo_info($buffer);
        });
    } catch (ProcessFailedException $e) {
        ox_echo_error($e->getMessage());
        return false;
    }
    return true;
}

function ox_mkdir($dir)
{
    $filesystem = new Filesystem();
    try {
        $filesystem->mkdir($dir, 0755);
    } catch (IOExceptionInterface $e) {
        ox_echo_error($e->getMessage());
        return false;
    }
    return true;
}

function ox_chown($dir, $owner, $group)
{
    $filesystem = new Filesystem();
    try {
        $filesystem->chown($dir, $owner, true);
        $filesystem->chgrp($dir, $group, true);
    } catch (IOExceptionInterface $e) {
        ox_echo_error($e->getMessage());
        return false;
    }
    return true;
}

function ox_mustache($string, $data = null)
{
    $mustache = new Mustache_Engine;
    return $mustache->render($string, $data);
}

function ox_template($template, $data = null)
{
    $mustache = new Mustache_Engine(['loader' => new Mustache_Loader_FilesystemLoader(OX_ROOT . '/templates')]);
    return $mustache->render($template, $data);
}

function ox_random_string($length = 6)
{
    $str = "";
    $characters = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
    $max = count($characters) - 1;
    for ($i = 0; $i < $length; $i ++) {
        $rand = mt_rand(0, $max);
        $str .= $characters[$rand];
    }
    return $str;
}
