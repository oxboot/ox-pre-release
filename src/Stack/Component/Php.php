<?php
namespace Ox\Stack\Component;

use Ox\App\Utils;
use Symfony\Component\Filesystem\Filesystem;

class Php extends AbstractComponent
{
    private static $php_www_config_file = '/etc/php/7.1/fpm/pool.d/www.conf';
    private static $php_www_old_config_file = '/etc/php/7.1/fpm/pool.d/www.conf.bak';

    public function install()
    {
        try {
            $this->utils->echoInfo('Installing PHP stack component, please wait...');
            $this->utils->echoInfo('Writing PHP stack component configuration...');
            $php_www_config = $this->utils->templateFile('stack/php/www');
            if (!$this->filesystem->exists(self::$php_www_old_config_file)) {
                $this->filesystem->copy(self::$php_www_config_file, self::$php_www_old_config_file);
            }
            $this->filesystem->dumpFile(self::$php_www_config_file, $php_www_config);
            $this->utils->exec('service php7.1-fpm restart');
        } catch (\Exception $e) {
            $this->utils->echoError('Error installing PHP stack component: '.$e->getMessage());
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        // TODO: Implement uninstall() method.
    }
}
