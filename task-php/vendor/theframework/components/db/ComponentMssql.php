<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @name TheFramework\Components\Db\ComponentMssql
 * @file ComponentMssql.php v1.4.1
 * @date 19-09-2017 04:56 SPAIN
 * @observations
 */

namespace TheFramework\Components\Db;

class ComponentMssql
{
    private $conn;
    private $isError;
    private $errors;
    private $affected;

    public function __construct($conn = [])
    {
        $this->isError = false;
        $this->errors = [];
        $this->conn = $conn;
    }

    private function getConnString()
    {
        $conn["sqlsrv:Server"] = (isset($this->conn["server"]) ? $this->conn["server"] : "");
        $conn["Database"] = (isset($this->conn["database"]) ? $this->conn["database"] : "");
        $conn["ConnectionPooling"] = (isset($this->conn["pool"]) ? $this->conn["pool"] : "0");

        $string = "";
        foreach($conn as $k => $v) {
            $string .= "$k=$v;";
        }

        return $string;
    }//get_conn_string

    public function query($sql)
    {
        try {
            $conn = $this->getConnString();
            //https://stackoverflow.com/questions/38671330/error-with-php7-and-sql-server-on-windows
            $pdo = new \PDO($conn, $this->conn["user"], $this->conn["password"]);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(\PDO::SQLSRV_ATTR_ENCODING, \PDO::SQLSRV_ENCODING_UTF8);
            //$oPdo->setAttribute(\PDO::SQLSRV_ATTR_ENCODING, \PDO::SQLSRV_ENCODING_SYSTEM);
            $cursor = $pdo->query($sql);
            if ($cursor === false) {
                $this->addError("exec-error: $sql");
            } else {
                //var_dump($stmt);
                $result = [];
                while($row = $cursor->fetch(\PDO::FETCH_ASSOC)) {
                    $result[] = $row;
                }
                $this->affected = count($result);
            }
        } catch(PDOException $e) {
            $message = "exception:{$e->getMessage()}";
            $this->addError($message);
        }
        return $result;
    }//query

    public function exec($sql)
    {
        try {
            $conn = $this->getConnString();
            //https://stackoverflow.com/questions/19577056/using-pdo-to-create-table
            $pdo = new \PDO($conn, $this->conn["user"], $this->conn["password"]);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $r = $pdo->exec($sql);
            $this->affected = $r;
            if ($r === false) {
                $this->addError("exec-error: $sql");
            }
        } catch(PDOException $e) {
            $message = "exception:{$e->getMessage()}";
            $this->addError($message);
        }
    }//exec

    private function addError($message)
    {
        $this->isError = true;
        $this->affected = -1;
        $this->errors[] = $message;
    }
    public function isError()
    {
        return $this->isError;
    }
    public function getErrors()
    {
        return $this->errors;
    }
    public function showErrors()
    {
        echo "<pre>".var_export($this->errors, 1);
    }

    public function addConn($k, $v)
    {
        $this->conn[$k] = $v;
    }
    public function getAffected()
    {
        return $this->affected;
    }

}//ComponentMssql
