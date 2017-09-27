<?php
namespace Ox\Stack;

use Ox\App\Utils;
use Symfony\Component\Filesystem\Filesystem;

class Composer extends AbstractComponent
{
    public function install()
    {
        try {
            $this->utils->echoInfo('Installing Composer stack component, please wait...');
            $this->utils->exec("php -r \"copy('https://getcomposer.org/installer', 'composer-setup.php'); if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;\"");
            $this->utils->exec('php composer-setup.php');
            $this->utils->exec("php -r \"unlink('composer-setup.php');\"");
            $this->utils->exec("mv composer.phar /usr/local/bin/composer");
            $this->utils->echoInfo('Writing Composer stack component configuration...');
            $this->utils->exec('service php7.1-fpm restart');
        } catch (\Exception $e) {
            $this->utils->echoError('Error installing Composer stack component: '.$e);
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        // TODO: Implement uninstall() method.
    }
}
