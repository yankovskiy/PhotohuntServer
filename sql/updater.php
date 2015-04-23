<?php
require_once '../www/include/config.php';
class Updater {
    const DIR = "./";
    private $mConnection;
    private $mFileList;

    function __construct() {
        $this->mConnection = new PDO(Config::DB_DSN, Config::DB_USERNAME, Config::DB_PASSWD,
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
    }
    
    private function getCurrentVersion() {
        $sql = "select `value` from `config` where `name` = 'version'";
        $row = $this->mConnection->query($sql)->fetch();
        return $row["value"];
    }

    private function loadFileList() {
        $files = scandir(self::DIR,  SCANDIR_SORT_NONE);
        
        $files = array_diff($files, array(".", "..", "updater.php", "photohunt.sql"));
        natsort($files);
        
        $this->mFileList = array();
        foreach ($files as $file) {
            $this->mFileList[] = $file;
        }
    }
    
    private function getMaxUpdateVersion() {
        $last = $this->mFileList[count($this->mFileList) - 1];
        return str_replace(".sql", "", str_replace("update_", "", $last));
    }
    
    private function runQuery($query) {
        $this->mConnection->exec($query);
    }
    
    public function run() {
        $this->loadFileList();
        $currVersion = $this->getCurrentVersion();
        $maxVersion = $this->getMaxUpdateVersion();
        
        for ($i = $currVersion + 1; $i <= $maxVersion; $i++) {
            $file = sprintf("update_%d.sql", $i);
            printf("%s updated\n", $file);
            $query = file_get_contents(self::DIR . $file);
            $this->runQuery($query);
        }
    }
}

$upd = new Updater();
$upd->run();
