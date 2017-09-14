<?php
namespace Ox;

use Symfony\Component\Filesystem\Filesystem;

class MySQL
{
    public static function install()
    {
        try {
            $fs = new Filesystem();
            ox_echo_info('Installing MariaDB 10.2, please wait...');
            ox_exec('apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xF1656F24C74CD1D8');
            ox_exec("add-apt-repository 'deb [arch=amd64,i386,ppc64el] http://ams2.mirrors.digitalocean.com/mariadb/repo/10.2/ubuntu xenial main'");
            ox_exec('apt-get update &>> /dev/null');
            $mysql_password = Utils::randomString(8);
            $mysql_config = "[client] \n user = root\n password = " . $mysql_password;
            ox_exec("echo \"mariadb-server mysql-server/root_password password " . $mysql_password . "\"" . " | debconf-set-selections");
            ox_exec("echo \"mariadb-server mysql-server/root_password_again password " . $mysql_password . "\"" . " | debconf-set-selections");
            ox_echo_info('Writting MySQL configuration...');
            $conf_path = '/etc/mysql/conf.d/my.cnf';
            $fs->dumpFile($conf_path, $mysql_config);
            ox_exec('apt-get -y install mariadb-server');
        } catch (\Exception $e) {
            ox_echo_error('Error installing MySQL: ' . $e);
            return false;
        }
        return true;
    }
}
