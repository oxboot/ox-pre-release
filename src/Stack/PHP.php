<?php
namespace Ox\Stack;

use Ox\App\Utils;
use Symfony\Component\Filesystem\Filesystem;

class PHP
{
    private static $php_www_config_file = '/etc/php/7.1/fpm/pool.d/www.conf';
    private static $php_www_old_config_file = '/etc/php/7.1/fpm/pool.d/www.conf.bak';

    public function install()
    {
        $utils = new Utils();
        $filesystem = new Filesystem();

        try {
            $utils->echoInfo('Installing PHP stack component, please wait...');
            $utils->echoInfo('Writing PHP stack component configuration...');
            $php_www_config = $utils->templateFile('stack/php/www');
            $filesystem->copy(self::$php_www_config_file, self::$php_www_old_config_file);
            $filesystem->dumpFile(self::$php_www_config_file, $php_www_config);
            $utils->exec('service php7.1-fpm restart');
        } catch (\Exception $e) {
            $utils->echoError('Error installing PHP stack component: '.$e->getMessage());
            return false;
        }
        return true;
    }
}
