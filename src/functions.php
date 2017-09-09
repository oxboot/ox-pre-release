<?php

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

function ox_echo($message, $color = 'white')
{
    $output = new ConsoleOutput();
    $output->writeln('<fg=' . $color .'>' . $message . '</>');
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
        return true;
    } catch (ProcessFailedException $e) {
        ox_echo_error($e->getMessage());
        return false;
    }
}
