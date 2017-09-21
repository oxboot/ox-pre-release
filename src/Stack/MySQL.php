<?php
namespace Ox\Stack;

use Ox\Utils;
use Symfony\Component\Filesystem\Filesystem;

class MySQL
{
    private static $conf_path = '/etc/mysql';
    private static $conf_file = '/etc/mysql/conf.d/my.cnf';

    public static function install()
    {
        try {
            $fs = new Filesystem();
            ox_echo_info('Installing MySQL stack component, please wait...');
            ox_exec('apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xF1656F24C74CD1D8');
            ox_exec("add-apt-repository 'deb [arch=amd64,i386,ppc64el] http://ams2.mirrors.digitalocean.com/mariadb/repo/10.2/ubuntu xenial main'");
            ox_exec('apt-get update &>> /dev/null');
            $mysql_password = Utils::randomString(8);
            $mysql_config = "[client] \nuser = root\npassword = ".$mysql_password;
            ox_exec("echo \"mariadb-server mysql-server/root_password password ".$mysql_password."\""." | debconf-set-selections");
            ox_exec("echo \"mariadb-server mysql-server/root_password_again password ".$mysql_password."\""." | debconf-set-selections");
            ox_echo_info('Writting MySQL stack component configuration...');
            $fs->dumpFile(self::$conf_file, $mysql_config);
            ox_echo_info('Installing MySQL server...');
            ox_exec('apt-get install -y mariadb-server');
            ox_exec('service mysql restart');
        } catch (\Exception $e) {
            ox_echo_error('Error installing MySQL stack component: '.$e);
            return false;
        }
        return true;
    }

    public static function uninstall()
    {
        try {
            $fs = new Filesystem();
            ox_echo_info('Uninstalling MySQL stack component, please wait...');
            ox_exec('apt-get -y purge mariadb-server');
            ox_exec('apt-get -y --purge autoremove');
            ox_echo_info('Remove MySQL configuration...');
            $fs->remove(self::$conf_path);
        } catch (\Exception $e) {
            ox_echo_error('Error uninstalling MySQL stack component: '.$e);
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
            $mysql_root = parse_ini_file(self::$conf_file);
            $db = self::connect($mysql_root['user'], $mysql_root['password']);
            $db->exec("CREATE USER '".$mysql_site_user."'@'localhost' IDENTIFIED BY '".$mysql_site_password."'");
            ox_echo_error('User '.$mysql_site_user.' created successful');
        } catch (\Exception $e) {
            ox_echo_error('Error creating user'.$mysql_site_user.': ' . $e);
            return false;
        }
        return true;
    }

    public static function deleteUser($mysql_site_user)
    {
        try {
            $mysql_root = parse_ini_file(self::$conf_file);
            $db = self::connect($mysql_root['user'], $mysql_root['password']);
            $db->exec("DROP USER '".$mysql_site_user."'@'localhost'");
            ox_echo_error('User '.$mysql_site_user.' deleted successful');
        } catch (\Exception $e) {
            ox_echo_error('Error deleting user'.$mysql_site_user.': ' . $e);
            return false;
        }
        return true;
    }

    public static function createDb($mysql_site_db)
    {
        try {
            $mysql_root = parse_ini_file(self::$conf_file);
            $db = self::connect($mysql_root['user'], $mysql_root['password']);
            $db->exec('CREATE DATABASE '.$mysql_site_db);
            ox_echo_error('Database '.$mysql_site_db.' created successful');
        } catch (\Exception $e) {
            ox_echo_error('Error creating database '.$mysql_site_db.':' . $e);
            return false;
        }
        return true;
    }

    public static function deleteDb($mysql_site_db)
    {
        try {
            $mysql_root = parse_ini_file(self::$conf_file);
            $db = self::connect($mysql_root['user'], $mysql_root['password']);
            $db->exec('DROP DATABASE '.$mysql_site_db);
            ox_echo_error('Database '.$mysql_site_db.' dropped successful');
        } catch (\Exception $e) {
            ox_echo_error('Database '.$mysql_site_db.' drop error: ' . $e);
            return false;
        }
        return true;
    }
}
