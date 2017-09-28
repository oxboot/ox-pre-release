<?php
namespace Ox\App;

use Symfony\Component\Filesystem\Filesystem;
use Noodlehaus\Config;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

class Db
{
    private $utils;
    private $filesystem;

    private $db_table;
    private $db_file;

    public function __construct($db_table)
    {
        $this->utils = new Utils();
        $this->filesystem = new Filesystem();

        $this->db_table = $db_table;
        $this->db_file = OX_DB_FOLDER.DS.$db_table.'.yml';
    }

    public function exists()
    {
        if ($this->filesystem->exists($this->db_file)) {
            return true;
        }
        return false;
    }

    public function create()
    {
        if ($this->exists()) {
            $this->utils->echoError('Ox database table '.$this->db_table.' already exists');
            return false;
        }
        try {
            $this->filesystem->dumpFile($this->db_file, '');
        } catch (ParseException $e) {
            $this->utils->echoError('Unable to create Ox database table '.$this->db_table.' : '.$e->getMessage());
            return false;
        }
        return true;
    }

    public function read()
    {
        try {
            $db_table_content = (array) Yaml::parse(file_get_contents($this->db_file));
        } catch (ParseException $e) {
            $this->utils->echoError('Unable to parse Ox database table '.$this->db_table.' : '.$e->getMessage());
            return null;
        }
        return $db_table_content;
    }

    public function write($db_content)
    {
        try {
            $this->filesystem->dumpFile($this->db_file, Yaml::dump($db_content));
        } catch (ParseException $e) {
            $this->utils->echoError('Unable to write Ox database table '.$this->db_table.' : '.$e->getMessage());
            return false;
        }
        return true;
    }
}
