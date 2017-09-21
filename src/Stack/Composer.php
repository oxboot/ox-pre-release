<?php
namespace Ox\Stack;

use Symfony\Component\Filesystem\Filesystem;

class Composer
{
    public static function install()
    {
        try {
            $fs = new Filesystem();
            ox_echo_info('Installing Composer stack component, please wait...');
            ox_exec("php -r \"copy('https://getcomposer.org/installer', 'composer-setup.php');\"");
            ox_exec("php -r \"if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;\"");
            ox_exec('php composer-setup.php');
            ox_exec("php -r \"unlink('composer-setup.php');\"");
            ox_exec("mv composer.phar /usr/local/bin/composer");
            ox_echo_info('Writing Composer stack component configuration...');
            ox_exec('service php7.1-fpm restart');
        } catch (\Exception $e) {
            ox_echo_error('Error installing Composer stack component: '.$e);
            return false;
        }
        return true;
    }
}
