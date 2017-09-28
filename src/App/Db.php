<?php
namespace Ox\App;

use Symfony\Component\Filesystem\Filesystem;
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

        if (!$this->filesystem->exists($this->db_file)) {
            try {
                $this->filesystem->dumpFile($this->db_file, '');
            } catch (ParseException $e) {
                $this->utils->echoError('Unable to create Ox database table '.$db_table.' : '.$e->getMessage());
                exit;
            }
        }
    }

    public function read()
    {
        try {
            $db_table_content = Yaml::parse(file_get_contents($this->db_file));
        } catch (ParseException $e) {
            $this->utils->echoError('Unable to parse Ox database table '.$this->db_table.' : '.$e->getMessage());
            return null;
        }
        return $db_table_content;
    }

    public function write()
    {
    }

    public function get()
    {
    }
}
