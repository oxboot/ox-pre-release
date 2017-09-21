<?php
namespace Ox\Stack;

use Symfony\Component\Filesystem\Filesystem;

class WPCLI
{
    public static function install()
    {
        try {
            $fs = new Filesystem();
            ox_echo_info('Installing WP CLI stack component, please wait...');
            ox_exec("curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar");
            ox_exec("chmod +x wp-cli.phar");
            ox_exec("mv wp-cli.phar /usr/local/bin/wp");
        } catch (\Exception $e) {
            ox_echo_error('Error installing WP CLI stack component: '.$e);
            return false;
        }
        return true;
    }
}
