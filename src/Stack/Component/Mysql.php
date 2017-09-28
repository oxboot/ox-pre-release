<?php
namespace Ox\Stack\Component;

use Ox\App\Utils;
use Symfony\Component\Filesystem\Filesystem;

class Mysql extends AbstractComponent
{
    const MYSQL_CONFIG_PATH = '/etc/mysql';
    const MYSQL_CONFIG_FILE = '/etc/mysql/conf.d/my.cnf';

    public function install()
    {
        $utils = new Utils();
        $filesystem = new Filesystem();

        try {
            $utils->echoInfo('Installing MySQL stack component, please wait...');
            $distro_name = $utils->distroName();
            $distro_version = $utils->distroVersion();
            if ($distro_name !== 'Ubuntu') {
                $utils->echoError('Ox do not support this distro: '.$distro_name);
                return false;
            }
            if ($distro_version === '14.04') {
                $utils->exec('apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xCBCB082A1BB943DB');
                $distro_codename = 'trusty';
            } elseif ($distro_version === '16.04') {
                $utils->exec('apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xF1656F24C74CD1D8');
                $distro_codename = 'xenial';
            } else {
                $utils->echoError('Ox do not support this Ubuntu version: '.$distro_version);
                return false;
            }
            $utils->exec(
                "add-apt-repository 'deb [arch=amd64,i386,ppc64el] "
                ."http://ams2.mirrors.digitalocean.com/mariadb/repo/10.2/ubuntu ".$distro_codename." main'"
            );
            $utils->exec('apt-get update &>> /dev/null');
            $mysql_password = $utils->randomString();
            $mysql_config = "[client] \nuser = root\npassword = ".$mysql_password;
            $utils->exec(
                "echo \"mariadb-server mysql-server/root_password password "
                .$mysql_password."\""." | debconf-set-selections"
            );
            $utils->exec(
                "echo \"mariadb-server mysql-server/root_password_again password "
                .$mysql_password."\""." | debconf-set-selections"
            );
            $utils->echoInfo('Writting MySQL stack component configuration...');
            $filesystem->dumpFile(self::MYSQL_CONFIG_FILE, $mysql_config);
            $utils->echoInfo('Installing MySQL server...');
            $utils->exec('apt-get install -y mariadb-server');
            $utils->exec('service mysql restart');
        } catch (\Exception $e) {
            $utils->echoError('Error installing MySQL stack component: '.$e);
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        $utils = new Utils();
        $filesystem = new Filesystem();

        try {
            $utils->echoInfo('Uninstalling MySQL stack component, please wait...');
            $utils->exec('apt-get -y purge mariadb-server');
            $utils->exec('apt-get -y --purge autoremove');
            $utils->echoInfo('Remove MySQL configuration...');
            $filesystem->remove(self::MYSQL_CONFIG_FILE);
        } catch (\Exception $e) {
            $utils->echoError('Error uninstalling MySQL stack component: '.$e);
            return false;
        }
        return true;
    }

    public function connect($mysql_user, $mysql_password)
    {
        $utils = new Utils();

        try {
            $db = new \PDO('mysql:host=localhost;', $mysql_user, $mysql_password);
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            $utils->echoError('Connect to MySQL not established: '.$e);
            return false;
        }
        return $db;
    }

    public function createUser($mysql_site_user, $mysql_site_password)
    {
        $utils = new Utils();

        try {
            $mysql_root = parse_ini_file(self::MYSQL_CONFIG_FILE);
            $db = $this->connect($mysql_root['user'], $mysql_root['password']);
            $db->exec("CREATE USER '".$mysql_site_user."'@'localhost' IDENTIFIED BY '".$mysql_site_password."'");
            $utils->echoSuccess('User '.$mysql_site_user.' created successful');
        } catch (\Exception $e) {
            $utils->echoError('Error creating user'.$mysql_site_user.': ' . $e);
            return false;
        }
        return true;
    }

    public function deleteUser($mysql_site_user)
    {
        $utils = new Utils();

        try {
            $mysql_root = parse_ini_file(self::MYSQL_CONFIG_FILE);
            $db = $this->connect($mysql_root['user'], $mysql_root['password']);
            $db->exec("DROP USER '".$mysql_site_user."'@'localhost'");
            $utils->echoSuccess('User '.$mysql_site_user.' deleted successful');
        } catch (\Exception $e) {
            $utils->echoError('Error deleting user'.$mysql_site_user.': ' . $e);
            return false;
        }
        return true;
    }

    public function createDb($mysql_site_db)
    {
        $utils = new Utils();

        try {
            $mysql_root = parse_ini_file(self::MYSQL_CONFIG_FILE);
            $db = self::connect($mysql_root['user'], $mysql_root['password']);
            $db->exec('CREATE DATABASE '.$mysql_site_db);
            $utils->echoInfo('Database '.$mysql_site_db.' created successful');
        } catch (\Exception $e) {
            $utils->echoError('Error creating database '.$mysql_site_db.':' . $e);
            return false;
        }
        return true;
    }

    public function deleteDb($mysql_site_db)
    {
        $utils = new Utils();

        try {
            $mysql_root = parse_ini_file(self::MYSQL_CONFIG_FILE);
            $db = self::connect($mysql_root['user'], $mysql_root['password']);
            $db->exec('DROP DATABASE '.$mysql_site_db);
            $utils->echoSuccess('Database '.$mysql_site_db.' dropped successful');
        } catch (\Exception $e) {
            $utils->echoError('Database '.$mysql_site_db.' drop error: ' . $e);
            return false;
        }
        return true;
    }


    public function grantDbUser($mysql_site_db, $mysql_site_user)
    {
        $utils = new Utils();

        try {
            $mysql_root = parse_ini_file(self::MYSQL_CONFIG_FILE);
            $db = self::connect($mysql_root['user'], $mysql_root['password']);
            $db->exec('GRANT ALL PRIVILEGES ON '.$mysql_site_db.'.* TO '.$mysql_site_user.'@localhost');
            $db->exec('FLUSH PRIVILEGES');
            $utils->echoSuccess(
                'Grant privileges of user '
                .$mysql_site_user.' to database '.$mysql_site_db.' done successful'
            );
        } catch (\Exception $e) {
            $utils->echoError(
                'Grant privileges of user '
                .$mysql_site_user.' to database '.$mysql_site_db.' error: ' . $e->getMessage()
            );
            return false;
        }
        return true;
    }
}
