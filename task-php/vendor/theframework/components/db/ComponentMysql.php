<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @name TheFramework\Components\Db\ComponentMysql
 * @file ComponentMysql.php v3.0.0
 * @date 14-01-2022 12:00 SPAIN
 * @observations
 */

namespace TheFramework\Components\Db;

use PDO;
use PDOException;
use Exception;

final class ComponentMysql
{
    private array $config = [];

    private int $affected = 0;
    private int $foundrows = 0;
    private int $lastid = -1;

    private bool $iserror = false;
    private array $errors = [];
    private string $sqlCount = "";

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public static function getInstance(array $config = []): self
    {
        return new self($config);
    }

    private function exception(string $message, int $code = 500): void
    {
        throw new Exception($message, $code);
    }

    private function getConfigString(): string
    {
        if (!$this->config) {
            $this->exception("empty connection config");
        }

        if (!($this->config["server"] ?? "")) {
            $this->exception("missing server in config");
        }
        if (!($this->config["database"] ?? "")) {
            $this->exception("missing database in config");
        }
        if (!($this->config["user"] ?? "")) {
            $this->exception("missing user in config");
        }
        if (!isset($this->config["password"])) {
            $this->exception("missing password in config");
        }

        $config["mysql:host"]   = $this->config["server"];
        $config["dbname"]       = $this->config["database"];
        $config["port"]         = $this->config["port"] ?? "3386";

        $strcon = "";
        foreach($config as $k => $v) {
            $strcon .= "$k=$v;";
        }

        //mysql:host=cont-mariadb-univ;dbname=db_mypromos;port=3306;
        return $strcon;
    }//_get_config_string

    private function getRowcol(array $result, ?int $icol = null, ?int $irow = null)
    {
        if (!$result) {
            return $result;
        }

        $row0 = $result[0];
        $fieldnames = array_keys($row0);

        if ($isrow = !is_null($irow)) {
            $result = $result[$irow] ?? [];
        }

        if (!is_null($icol)) {
            $colname = $fieldnames[$icol] ?? "";
            if (!$colname) {
                $this->exception("no column in position $icol");
            }
            if ($isrow) {
                return $result[$colname];
            }
            return array_column($result, $colname);
        }

        return $result;
    }

    private function getPdo(): PDO
    {
        $strcon = $this->getConfigString();
        $pdo = new PDO(
            $strcon,
            $this->config["user"],
            $this->config["password"],
            [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    //? en return pq puede que el valor de la celda sea null
    public function query(string $sql, ?int $icol = null, ?int $irow = null): array|string|null
    {
        $this->foundrows = 0;
        $pdo = $this->getPdo();
        $this->log($sql, "componentmysql.query");

        if (($cursor = $pdo->query($sql)) === false) {
            $this->exception("pdo.query error");
        }

        $result = [];
        while($row = $cursor->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }

        //@deprecated https://dev.mysql.com/worklog/task/?id=12615
        //mejor es hacer un count(*) sin limit
        if ($this->sqlCount) {
            $this->log($this->sqlCount, "componentmysql.count");
            $this->foundrows = $pdo->query($this->sqlCount)->fetch(PDO::FETCH_COLUMN);
        }
        return $this->getRowcol($result, $icol, $irow);
    }//query

    public function exec(string $sql)
    {
        $this->affected = 0;
        $this->lastid = -1;

        $pdo = $this->getPdo();
        $this->log($sql, "componentmysql.exec");
        if (($result = $pdo->exec($sql)) === false) {
            $this->exception("pdo.exec error");
        }

        $this->affected = $result;
        if (strstr($sql, "INSERT INTO ")) {
            $this->lastid = $pdo->lastInsertId();
        }

        return $result;
    }//exec

    private function log($var, ?string $title = null): void
    {
        if (class_exists("\BOOT") && class_exists("\TheFramework\Components\ComponentLog")) {
            $log = new \TheFramework\Components\ComponentLog("sql", \BOOT::PATH_LOGS);
            $log->save($var, "-- ". $title);
        }

        /**
        para este proy de momento no se usa kafka
        if (function_exists("get_log_producer")) {
            //get_log_producer()->send($mxVar, "-- ". $title, "sql");
        }
        */
    }

    public function addConfig(string $k, string $v): self
    {
        $this->config[$k] = $v;
        return $this;
    }
    public function setConfig(array $config = []): self
    {
        $this->config = $config;
        return $this;
    }

    public function getConfig(string $key): ?string
    {
        return (string) ($this->config[$key] ?? "");
    }

    //on insert
    public function getLastid()
    {
        return $this->lastid;
    }

    //on select
    public function setSqlcount(string $sql = ""): self
    {
        $this->sqlCount = $sql;
        return $this;
    }
    public function getFoundrows()
    {
        return $this->foundrows;
    }

    //on update/delete
    public function getAffected()
    {
        return $this->affected;
    }

    public function isError(): bool
    {
        return  $this->iserror;
    }

}//ComponentMysql
