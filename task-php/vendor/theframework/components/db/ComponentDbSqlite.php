<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @name TheFramework\Components\Db\ComponentDbSqlite
 * @file ComponentDbSqlite.php v2.0.0
 * @date 19-09-2017 04:56 SPAIN
 * @observations
 */

namespace TheFramework\Components\Db;

class ComponentDbSqlite
{
    /**
     * @var PDO
     */
    private static $pdo;
    private $dbName;
    private $pathFolder;
    private $pathFile;

    private $isError;
    private $isPersistent;
    private $affectedRows;

    public const DS = DIRECTORY_SEPARATOR;

    public function __construct($pathFolder = "", $dbName = "app.sqlite3")
    {
        $this->pathFolder = $pathFolder;
        $this->dbName = $dbName;

        if (!$this->pathFolder) {
            $this->pathFolder = TFW_PATH_APPLICATIONDS."appdb";
        }
        if (!$this->dbName) {
            $this->dbName = "app.sqlite3";
        }
        $this->isPersistent = true;
    }

    private function isConfigok()
    {
        $isOk = true;
        $isOk = ($isOk && $this->dbName);
        $isOk = ($isOk && is_file($this->pathFile));
        return $isOk;
    }

    private function connOpen()
    {
        //pathfile
        $this->setPathfile();
        if (!$this->isConfigok()) {
            $message = "conn_open.error in config. Db:$this->pathFile";
            $this->addMessage($message);
            return;
        }
        //data source name
        $dSN = "sqlite:$this->pathFile,charset=utf8mb4";
        try {
            //pr($sDSN,"DSN");
            self::$pdo  = new \PDO($dSN);
            self::$pdo->setAttribute(\PDO::ATTR_TIMEOUT, 3600);
        } catch (\PDOException $e) {
            $message = "PDO Exception: DSN:$dSN, message:".$e->getMessage();
            $this->addMessage($message);
        }
    }//conn_open

    public function execute($sql)
    {
        $isAffected = false;

        if (trim($sql)) {
            if ($this->isNoterror()) {
                $this->connOpen();
                if ($this->isNoterror()) {
                    $this->affectedRows = self::$pdo->exec($sql);
                    if ($this->affectedRows === false) {
                        $this->addMessage("ERROR: PDO->exec(SQL)");
                        $this->addMessage($sql);
                    }
                    $this->connClose();
                }//if not error
            }//if not error
        } else {
            $message = "execute.sql empty";
            $this->addMessage($message);
        }
        return $isAffected;
    }//execute

    public function query($sql)
    {
        if (trim($sql)) {
            if ($this->isNoterror()) {
                $this->connOpen();
                if ($this->isNoterror()) {
                    if (self::$pdo) {
                        $rows = self::$pdo->query($sql);
                        $this->connClose();
                        return $rows;
                    }
                }//if not error
            }//if not error
        } else {
            $message = "query.sql empty";
            $this->addMessage($message);
        }
        return [];
    }//query

    public function connClose()
    {
        if (!$this->isPersistent) {
            self::$pdo = null;
        }
    }

    private function addMessage($message, $type = "error")
    {
        if ($type === "error") {
            $this->isError = true;
        }
        $this->messages[$type][] = $message;
    }

    private function setPathfile()
    {
        $this->pathFile = realpath("$this->pathFolder/$this->dbName");
    }

    public function isPersistent($isOn = true)
    {
        $this->isPersistent = $isOn;
    }
    public function setFolder($value)
    {
        $this->pathFolder = $value;
    }
    public function setDbname($value)
    {
        $this->dbName = $value;
    }
    public function getErrors()
    {
        return isset($this->messages["error"]) ? $this->messages["error"] : [];
    }
    private function isNoterror()
    {
        return !$this->isError;
    }
    public function isError()
    {
        return $this->isError;
    }
    public function getDebug()
    {
        return isset($this->messages["debug"]) ? $this->messages["debug"] : [];
    }
    public function getAffectedRows()
    {
        return $this->affectedRows;
    }
    public function resetErrors()
    {
        $this->messages["error"] = [];
        $this->isError = false;
    }

}//ComponentDbSqlite
