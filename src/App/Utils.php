<?php
namespace Ox\App;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class Utils
{
    public function echoStandard($message, $color = 'white')
    {
        if ($message) {
            $output = new ConsoleOutput();
            $output->writeln('<fg=' . $color .'>' . $message . '</>');
        }
    }

    public function echoInfo($message)
    {
        $this->echoStandard($message, 'blue');
    }

    public function echoSuccess($message)
    {
        $this->echoStandard($message, 'green');
    }

    public function echoError($message)
    {
        $this->echoStandard($message, 'red');
    }

    public function distroName()
    {
        $process = new Process('lsb_release -is');
        try {
            $process->run();
        } catch (ProcessFailedException $e) {
            $this->echoError($e->getMessage());
            return false;
        }
        return trim($process->getOutput());
    }

    public function distroVersion()
    {
        $process = new Process('lsb_release -rs');
        try {
            $process->run();
        } catch (ProcessFailedException $e) {
            $this->echoError($e->getMessage());
            return false;
        }
        return trim($process->getOutput());
    }

    public function exec($command, $user = null)
    {
        if (isset($user)) {
            $command = "su ".$user." -s /bin/bash -c \"".$command."\"";
        }
        $process = new Process($command);
        try {
            $process->setTimeout(3600);
            $process->mustRun(function ($type, $buffer) {
                $this->echoInfo($buffer);
            });
        } catch (ProcessFailedException $e) {
            $this->echoError($e->getMessage());
            return false;
        }
        return true;
    }

    public function mkdir($dir)
    {
        $filesystem = new Filesystem();
        try {
            $filesystem->mkdir($dir, 0755);
        } catch (IOExceptionInterface $e) {
            $this->echoError($e->getMessage());
            return false;
        }
        return true;
    }

    public function chown($dir, $owner, $group)
    {
        $filesystem = new Filesystem();
        try {
            $filesystem->chown($dir, $owner, true);
            $filesystem->chgrp($dir, $group, true);
        } catch (IOExceptionInterface $e) {
            $this->echoError($e->getMessage());
            return false;
        }
        return true;
    }

    public function templateString($string, $data = null)
    {
        $mustache = new \Mustache_Engine;
        return $mustache->render($string, $data);
    }

    public function templateFile($template, $data = null)
    {
        $mustache = new \Mustache_Engine(['loader' => new \Mustache_Loader_FilesystemLoader(OX_ROOT . '/templates')]);
        return $mustache->render($template, $data);
    }

    public function randomString($length = 8)
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
}
