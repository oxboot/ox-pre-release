<?php
namespace Ox;

use Noodlehaus\Config;
use Symfony\Component\Filesystem\Filesystem;

class MySQL
{
    private static $conf_path = '/etc/mysql/conf.d/my.cnf';

    public static function install()
    {
        try {
            $fs = new Filesystem();
            ox_echo_info('Installing MariaDB 10.2, please wait...');
            ox_exec('apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xF1656F24C74CD1D8');
            ox_exec("add-apt-repository 'deb [arch=amd64,i386,ppc64el] http://ams2.mirrors.digitalocean.com/mariadb/repo/10.2/ubuntu xenial main'");
            ox_exec('apt-get update &>> /dev/null');
            $mysql_password = Utils::randomString(8);
            $mysql_config = "[client] \nuser = root\npassword = ".$mysql_password;
            ox_exec("echo \"mariadb-server mysql-server/root_password password ".$mysql_password."\""." | debconf-set-selections");
            ox_exec("echo \"mariadb-server mysql-server/root_password_again password ".$mysql_password."\""." | debconf-set-selections");
            ox_echo_info('Writting MySQL configuration...');
            $fs->dumpFile(self::$conf_path, $mysql_config);
            ox_exec('apt-get -y install mariadb-server');
        } catch (\Exception $e) {
            ox_echo_error('Error installing MySQL: '.$e);
            return false;
        }
        return true;
    }

    public static function connect($mysql_user, $mysql_password)
    {
        try {
            $db = new \PDO('mysql:host=localhost;', $mysql_user, $mysql_password);
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            ox_echo_error('Connect to MySQL not established: '.$e);
            return false;
        }
        return $db;
    }

    public static function createUser($mysql_site_user, $mysql_site_password)
    {
        try {
            $db = self::connect();
        } catch (\Exception $e) {
            ox_echo_error('Error creating user: ' . $e);
            return false;
        }
        return true;
    }

    public static function createDb($mysql_site_db)
    {
        try {
            var_dump(Config::load(self::$conf_path));
        } catch (\Exception $e) {
            ox_echo_error('Error creating database: ' . $e);
            return false;
        }
        return true;
    }
}
