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
        try {
            $this->utils->echoInfo('Installing MySQL stack component, please wait...');
            $distro_name = $this->utils->distroName();
            $distro_version = $this->utils->distroVersion();
            if ($distro_name !== 'Ubuntu') {
                $this->utils->echoError('Ox do not support this distro: '.$distro_name);
                return false;
            }
            if ($distro_version === '14.04') {
                $this->utils->exec('apt-key adv --recv-keys --keyserver '
                    .'hkp://keyserver.ubuntu.com:80 0xCBCB082A1BB943DB');
                $distro_codename = 'trusty';
            } elseif ($distro_version === '16.04') {
                $this->utils->exec('apt-key adv --recv-keys --keyserver '
                    .'hkp://keyserver.ubuntu.com:80 0xF1656F24C74CD1D8');
                $distro_codename = 'xenial';
            } else {
                $this->utils->echoError('Ox do not support this Ubuntu version: '.$distro_version);
                return false;
            }
            $this->utils->exec(
                "add-apt-repository 'deb [arch=amd64,i386,ppc64el] "
                ."http://ams2.mirrors.digitalocean.com/mariadb/repo/10.2/ubuntu ".$distro_codename." main'"
            );
            $this->utils->exec('apt-get update &>> /dev/null');
            $mysql_password = $this->utils->randomString();
            $mysql_config = "[client] \nuser = root\npassword = ".$mysql_password;
            $this->utils->exec(
                "echo \"mariadb-server mysql-server/root_password password "
                .$mysql_password."\""." | debconf-set-selections"
            );
            $this->utils->exec(
                "echo \"mariadb-server mysql-server/root_password_again password "
                .$mysql_password."\""." | debconf-set-selections"
            );
            $this->utils->echoInfo('Writting MySQL stack component configuration...');
            $this->filesystem->dumpFile(self::MYSQL_CONFIG_FILE, $mysql_config);
            $this->utils->echoInfo('Installing MySQL server...');
            $this->utils->exec('apt-get install -y mariadb-server');
            $this->utils->exec('service mysql restart');
        } catch (\Exception $e) {
            $this->utils->echoError('Error installing MySQL stack component: '.$e);
            return false;
        }
        return true;
    }

    public function uninstall()
    {
        try {
            $this->utils->echoInfo('Uninstalling MySQL stack component, please wait...');
            $this->utils->exec('apt-get -y purge mariadb-server');
            $this->utils->exec('apt-get -y --purge autoremove');
            $this->utils->echoInfo('Remove MySQL configuration...');
            $this->filesystem->remove(self::MYSQL_CONFIG_FILE);
        } catch (\Exception $e) {
            $this->utils->echoError('Error uninstalling MySQL stack component: '.$e);
            return false;
        }
        return true;
    }

    public function connect($mysql_user, $mysql_password)
    {
        try {
            $database = new \PDO('mysql:host=localhost;', $mysql_user, $mysql_password);
            $database->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            $this->utils->echoError('Connect to MySQL not established: '.$e);
            return false;
        }
        return $database;
    }

    public function createUser($mysql_site_user, $mysql_site_password)
    {
        try {
            $mysql_root = parse_ini_file(self::MYSQL_CONFIG_FILE);
            $database = $this->connect($mysql_root['user'], $mysql_root['password']);
            $database->exec("CREATE USER '".$mysql_site_user."'@'localhost' IDENTIFIED BY '".$mysql_site_password."'");
            $this->utils->echoSuccess('User '.$mysql_site_user.' created successful');
        } catch (\Exception $e) {
            $this->utils->echoError('Error creating user'.$mysql_site_user.': ' . $e);
            return false;
        }
        return true;
    }

    public function deleteUser($mysql_site_user)
    {
        try {
            $mysql_root = parse_ini_file(self::MYSQL_CONFIG_FILE);
            $database = $this->connect($mysql_root['user'], $mysql_root['password']);
            $database->exec("DROP USER '".$mysql_site_user."'@'localhost'");
            $this->utils->echoSuccess('User '.$mysql_site_user.' deleted successful');
        } catch (\Exception $e) {
            $this->utils->echoError('Error deleting user'.$mysql_site_user.': ' . $e);
            return false;
        }
        return true;
    }

    public function createDb($mysql_site_db)
    {
        try {
            $mysql_root = parse_ini_file(self::MYSQL_CONFIG_FILE);
            $database = self::connect($mysql_root['user'], $mysql_root['password']);
            $database->exec('CREATE DATABASE '.$mysql_site_db);
            $this->utils->echoInfo('Database '.$mysql_site_db.' created successful');
        } catch (\Exception $e) {
            $this->utils->echoError('Error creating database '.$mysql_site_db.':' . $e);
            return false;
        }
        return true;
    }

    public function deleteDb($mysql_site_db)
    {
        try {
            $mysql_root = parse_ini_file(self::MYSQL_CONFIG_FILE);
            $database = self::connect($mysql_root['user'], $mysql_root['password']);
            $database->exec('DROP DATABASE '.$mysql_site_db);
            $this->utils->echoSuccess('Database '.$mysql_site_db.' dropped successful');
        } catch (\Exception $e) {
            $this->utils->echoError('Database '.$mysql_site_db.' drop error: ' . $e);
            return false;
        }
        return true;
    }


    public function grantDbUser($mysql_site_db, $mysql_site_user)
    {
        try {
            $mysql_root = parse_ini_file(self::MYSQL_CONFIG_FILE);
            $database = self::connect($mysql_root['user'], $mysql_root['password']);
            $database->exec('GRANT ALL PRIVILEGES ON '.$mysql_site_db.'.* TO '.$mysql_site_user.'@localhost');
            $database->exec('FLUSH PRIVILEGES');
            $this->utils->echoSuccess(
                'Grant privileges of user '
                .$mysql_site_user.' to database '.$mysql_site_db.' done successful'
            );
        } catch (\Exception $e) {
            $this->utils->echoError(
                'Grant privileges of user '
                .$mysql_site_user.' to database '.$mysql_site_db.' error: ' . $e->getMessage()
            );
            return false;
        }
        return true;
    }
}
