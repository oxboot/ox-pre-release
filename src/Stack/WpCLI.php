<?php
namespace Ox\Stack;

use Ox\App\Utils;
use Symfony\Component\Filesystem\Filesystem;

class WpCLI
{
    public static function install()
    {
        $utils = new Utils();
        $filesystem = new Filesystem();

        try {
            $utils->echoInfo('Installing WP CLI stack component, please wait...');
            $utils->exec("curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli-nightly.phar");
            $utils->exec("chmod +x wp-cli.phar");
            $utils->exec("mv wp-cli.phar /usr/local/bin/wp");
        } catch (\Exception $e) {
            $utils->echoError('Error installing WP CLI stack component: '.$e->getMessage());
            return false;
        }
        return true;
    }
}
