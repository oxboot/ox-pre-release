<?php
namespace Ox\Stack;

use Symfony\Component\Filesystem\Filesystem;

class PHP
{

    private static $php_www_config_file = '/etc/php/7.1/fpm/pool.d/www.conf';
    private static $php_www_old_config_file = '/etc/php/7.1/fpm/pool.d/www.conf.bak';

    public static function install()
    {
        try {
            $fs = new Filesystem();
            ox_echo_info('Installing PHP stack component, please wait...');
            ox_echo_info('Writing PHP stack component configuration...');
            $php_www_config = ox_template('stack/php/www');
            $fs->copy(self::$php_www_config_file, self::$php_www_old_config_file);
            $fs->dumpFile(self::$php_www_config_file, $php_www_config);
            ox_exec('service php7.1-fpm restart');
        } catch (\Exception $e) {
            ox_echo_error('Error installing PHP stack component: '.$e);
            return false;
        }
        return true;
    }
}
