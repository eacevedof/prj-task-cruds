<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @name TheFramework\Components\Db\ComponentMssqlExport
 * @file ComponentMssqlExport.php v2.1.0B
 * @date 30-03-2018 12:06 SPAIN
 * @observations
 */

namespace TheFramework\Components\Db\Integration;

use TheFramework\Components\ComponentLog;
use TheFramework\Components\Db\ComponentMssql;

class ComponentMssqlExport
{
    private $conn;
    private $isError;
    private $errors;
    private $affected;
    private $db;

    private $numeric = ["float","real","int","smallint","money","bit"];
    private $string = ["varchar","text","char"];
    private $date = ["datetime","smalldatetime"];
    private $noLen = ["float","datetime","real","smalldatetime","int","text","smallint","money"
                        ,"image","varbinary"];
    //binarios
    private $binary = ["image","varbinary"];

    private $motorTo = "mssql";

    public function __construct($conn = ["server" => "","database" => "","user" => "","password" => ""])
    {
        $this->isError = false;
        $this->errors = [];
        $this->conn = $conn;
        $this->db = new ComponentMssql($this->conn);
    }//__construct

    private function getFieldmap()
    {
        $typeMap = [];
        $typeMap["mssql"]["1"] = "float";
        $typeMap["mssql"]["2"] = "int";
        $typeMap["mssql"]["3"] = "numeric";
        $typeMap["mssql"]["4"] = "varchar";
        $typeMap["mssql"]["5"] = "datetime";
        $typeMap["mssql"]["6"] = "real";
        $typeMap["mssql"]["7"] = "smalldatetime";
        $typeMap["mssql"]["8"] = "decimal";
        $typeMap["mssql"]["9"] = "money";

        $typeMap["mysql"]["1"] = "float";
        $typeMap["mysql"]["2"] = "int";
        $typeMap["mysql"]["3"] = "decimal";
        $typeMap["mysql"]["4"] = "varchar";
        $typeMap["mysql"]["5"] = "datetime";
        $typeMap["mysql"]["6"] = "decimal";
        $typeMap["mysql"]["7"] = "datetime";
        $typeMap["mysql"]["8"] = "decimal";//10,8
        $typeMap["mysql"]["9"] = "decimal";//10,0
    }//get_fieldmap

    private function getFieldtypeMap($type, $motorSrc, $motorTrg)
    {
        //https://support.dbconvert.com/hc/en-us/articles/202952551-Mapping-MySQL-and-SQL-Server-Data-Types
        $types = [
            "mssql" => [
                "bit" => [
                    "mysql" => "tinyint",
                    "sqlite" => "INTEGER",
                    ],
                "int" => [
                    "mysql" => "int",
                    "sqlite" => "INTEGER",

                    ],
                "tinyint" => [
                    "mysql" => "TINYINT",
                    "sqlite" => "INTEGER",
                    ],
                "smallint" => [
                    "mysql" => "SMALLINT",
                    "sqlite" => "INTEGER",
                    ],
                "varchar" => [
                    "mysql" => "varchar",
                    "sqlite" => "TEXT",
                ],
                "nvarchar" => [
                    "mysql" => "TEXT",
                    "sqlite" => "TEXT",
                ],
                "char" => [
                    "mysql" => "char",
                    "sqlite" => "TEXT",
                ],
                "nchar" => [
                    "mysql" => "char",
                    "sqlite" => "TEXT",
                ],
                "text" => [
                    "mysql" => "text",
                    "sqlite" => "TEXT",
                ],
                "ntext" => [
                    "mysql" => "text",
                    "sqlite" => "TEXT",
                ],
                "datetime" => [
                    "mysql" => "DATETIME",
                    "sqlite" => "TEXT",
                ],
                "smalldatetime" => [
                    "mysql" => "TIMESTAMP",
                    "sqlite" => "TEXT",
                ],
                "numeric" => [
                    "mysql" => "numeric",
                    "sqlite" => "REAL"
                ],
                "real" => [
                    "mysql" => "real",
                    "sqlite" => "REAL"
                ],
                "decimal" => [
                    "mysql" => "numeric",
                    "sqlite" => "REAL"
                ],
                "float" => [
                    "mysql" => "float",
                    "sqlite" => "REAL"
                ],
                "money" => [
                    "mysql" => "numeric",
                    "sqlite" => "REAL"
                ],
                "image" => [
                    "mysql" => "LONGBLOB",
                    "sqlite" => "TEXT"
                ],
                "varbinary" => [
                    "mysql" => "LONGBLOB",
                    "sqlite" => "TEXT"
                ],
            ],//mssql
            "mysql" => [
                "int" => [
                    "sqlite" => "INTEGER",
                    "mssql" => "int"
                    ],
                "tinyint" => [
                    "sqlite" => "INTEGER",
                    "mssql" => "tinyint"
                    ],
                "smallint" => [
                    "sqlite" => "INTEGER",
                    "mssql" => "smallint"
                    ],
                "varchar" => [
                    "sqlite" => "TEXT",
                    "mssql" => "varchar"
                ],
                "char" => [
                    "sqlite" => "TEXT",
                    "mssql" => "char"
                ],
                "text" => [
                    "sqlite" => "TEXT",
                    "mssql" => "text"
                ],
                "datetime" => [
                    "sqlite" => "TEXT",
                    "mssql" => "varchar"
                ],
                "decimal" => [
                    "sqlite" => "REAL",
                    "mssql" => "numeric"
                ],
                "float" => [
                    "sqlite" => "REAL",
                    "mssql" => "numeric"
                ]
            ],//mysql
            "sqlite" => [
                "INTEGER" => [
                    "mysql" => "int",
                    "mssql" => "int"
                ],
                "NUMERIC" => [
                    "mysql" => "decimal",
                    "mssql" => "numeric"
                ],
                "REAL" => [
                    "mysql" => "decimal",
                    "mssql" => "float"
                ],
                "TEXT" => [
                    "mysql" => "text",
                    "mssql" => "text"
                ],
                "BLOB" => [
                    "mysql" => "blob",
                    "mssql" => "-"
                ],
            ]
        ];
        return isset($types[$motorSrc][$type][$motorTrg]) ? $types[$motorSrc][$type][$motorTrg] : [];
    }//get_fieldtype_map

    private function log($text, $title = "", $type = "debug")
    {
        $pathLogs = $_SERVER["DOCUMENT_ROOT"].DIRECTORY_SEPARATOR."logs";
        $pathLogs = realpath($pathLogs);
        $log = new ComponentLog($type, $pathLogs);
        $log->save($text, $title);
    }//log

    public function getTables()
    {
        $ql = "/*ComponentMssqlExport.get_tables*/
        -- a esta consulta no le hace falta la base de datos. Se ajusta solo a las tablas del 
        -- esquema en el que se esta
        SELECT DISTINCT LOWER(sqltable.name) AS table_id
        ,sqltable.name AS table_name
        ,LTRIM(RTRIM(LOWER(sqltable.xtype))) AS otype
        FROM sysobjects sqltable
        WHERE 1=1
        AND (sqltable.xtype = 'U'/*tablees*/ OR sqltable.xtype = 'V'/*views*/)
        ORDER BY sqltable.name
        ";
        $this->log($ql, "get_tables");
        $tables = $this->db->query($ql);
        return $tables;
    }//get_tables

    public function getFields($tableName)
    {
        $ql = "/*ComponentMssqlExport.get_fields*/
        SELECT LOWER(cols.name) AS id
        ,' '+LOWER(cols.name)+' - '+types.name +'('
        +CASE types.name 
            WHEN 'text' THEN '1000000' 
            ELSE CONVERT(VARCHAR,cols.Length) 
        END +')' AS description
        FROM syscolumns AS cols
        INNER JOIN systypes AS types 
        ON cols.xtype=types.xtype
        INNER JOIN sysobjects AS tables 
        ON tables.id=cols.id
        AND tables.name = '$tableName'
        ORDER BY cols.colid ASC";

        $this->log($ql, "get_fields");
        $tmp = $this->db->query($ql);
        foreach($tmp as $t) {
            $tables[$t["id"]] = $t["description"];
        }

        return $tables;
    }//get_fields

    public function getFieldsInfo($tableName)
    {
        $ql = "/*ComponentMssqlExport.get_fields_info*/
        SELECT field_name
        ,MAX(field_type) AS field_type
        ,MAX(field_length) AS field_length
        ,MAX(defvalue) AS defvalue
        ,MAX(is_nullable) AS is_nullable
        ,MAX(ispk) AS ispk
        ,MAX(column_id) AS column_id
        FROM
        (
            SELECT DISTINCT field_name
            ,field_type
            ,CASE field_type
                WHEN 'varchar' THEN CONVERT(VARCHAR,mxlen)
                WHEN 'int' THEN '11'
                WHEN 'float' THEN '-'
                WHEN 'real' THEN '-'
                WHEN 'money' THEN '10,0'
                WHEN 'datetime' THEN '-'
                WHEN 'smalldatetime' THEN '-'
                WHEN 'varbinary' THEN '-'
                WHEN 'image' THEN '-'
                WHEN 'numeric' THEN CONVERT(VARCHAR,intpos)+','+CONVERT(VARCHAR,floatpos)
                WHEN 'decimal' THEN CONVERT(VARCHAR,intpos)+','+CONVERT(VARCHAR,floatpos)
                ELSE
                    CONVERT(VARCHAR,mxlen)
            END AS field_length
            ,REPLACE(REPLACE(ISNULL(defvalue,'NULL'),'(',''),')','') defvalue
            ,CONVERT(VARCHAR(30),is_nullable) is_nullable
            ,CONVERT(VARCHAR(30),ispk) ispk
            ,column_id
            FROM
            (
                SELECT DISTINCT syscols.name AS field_name
                ,systypes.name AS field_type 
                ,syscols.max_length AS mxlen
                ,syscols.precision AS intpos
                ,syscols.scale AS floatpos
                ,syscols.is_nullable AS 'is_nullable'
                ,extra.defvalue
                ,ISNULL(sysindexes.is_primary_key,0) AS ispk
                ,syscols.column_id
                FROM sys.columns AS syscols
                INNER JOIN 
                (
                    SELECT TABLE_NAME AS table_name
                    ,COLUMN_NAME AS field_name
                    ,COLUMN_DEFAULT AS defvalue
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE 1=1
                    AND TABLE_NAME='$tableName'
                    AND TABLE_CATALOG='{$this->conn["database"]}'
                ) AS extra
                ON syscols.object_id = OBJECT_ID(extra.table_name)
                AND syscols.name = extra.field_name
                INNER JOIN sys.types AS systypes 
                ON syscols.user_type_id = systypes.user_type_id
                LEFT OUTER JOIN sys.index_columns AS colsidx 
                ON colsidx.object_id = syscols.object_id 
                AND colsidx.column_id = syscols.column_id
                LEFT OUTER JOIN sys.indexes AS sysindexes
                ON colsidx.object_id = sysindexes.object_id 
                AND colsidx.index_id = sysindexes.index_id
                WHERE 1=1
                AND syscols.object_id = OBJECT_ID('$tableName')
            ) AS tabledef
        ) AS a
        GROUP BY field_name
        ORDER BY column_id
        ";
        $this->log($ql, "get_fields_info");
        $fields = $this->db->query($ql);
        return $fields;
    }//get_fields_info

    public function getInsertBulk($tableName = null, $isDelete = 1)
    {
        switch($this->motorTo) {
            case "mysql":
                return $this->getInsertBulkMysql($tableName, $isDelete);
                break;
            case "sqlite":
                return $this->getInsertBulkSelf($tableName, $isDelete);
                break;
            default:
                return $this->getInsertBulkSelf($tableName, $isDelete);
                break;
        }
    }//get_insert_bulk

    private function getInsertBulkSelf($tableName = null, $isDelete = 1)
    {
        $now = date("Ymd-His");
        $tables = $this->getTables();
        if ($tableName) {
            $tables = array_filter($tables, function ($item) use ($tableName) {
                //print_r($arItem);
                return $item["table_name"] === $tableName;
            });
        }
        //die;
        //echo "<pre>"; print_r($arTables);die;
        $lines = ["/*database $now*/\n USE {$this->conn["database"]}-x"];
        $lines = ["/*database $now*/\n USE db_killme"];

        foreach($tables as $table) {
            //vistas
            if ($table["otype"] == "v") {
                continue;
            }
            $tableName = $table["table_name"];

            if ($isDelete) {
                $lines[] = "-- DELETE FROM $tableName;";
            }

            $fields = $this->getFieldsInfo($tableName, 1);
            //print_r($arFields);die;
            $selects = [];
            foreach($fields as $field) {
                $selects[] = $field["field_name"];
            }

            $orderBy = "1";
            if (in_array("id", $selects)) {
                $orderBy = "id";
            }
            if (in_array("idn", $selects)) {
                $orderBy = "idn";
            }

            $select = implode("],[", $selects);
            $select = "[$select]";

            $ql = "SELECT $select FROM $tableName ORDER BY $orderBy ASC";
            //print_r($sSQL);die;
            $rows = $this->db->query($ql);
            if ($rows) {
                $end = end($rows);
                foreach($rows as $row) {
                    $insert = "INSERT INTO $tableName ($select) VALUES(";
                    $ins = [];
                    foreach($selects as $fieldName) {
                        $fieldType = $this->getFieldtype($fieldName, $fields);
                        $value = $row[$fieldName];
                        $value = str_replace("'", "''", $value);
                        $value = "'$value'";
                        if ($value === "''") {
                            if ($this->isNullable($fieldName, $fields)
                               || in_array($fieldType, $this->numeric)
                               || in_array($fieldType, $this->date)) {
                                $value = "NULL";
                            }
                        }
                        $ins[] = $value;
                    }
                    $insert .= implode(",", $ins);
                    $insert .= ")";

                    if ($end == $row) {
                        $insert .= ";";
                    } else {
                        $insert .= ";";
                    }

                    $lines[] = $insert;
                }//foreach arRows
            }//if arRows
            else {
                $lines[] = "-- no rows for $tableName";
            }
        }//foreach tables
        $insert = implode("\n", $lines);
        return $insert;
    }//get_insert_bulk_self

    private function getInsertBulkMysql($tableName = null, $isDelete = 1)
    {
        $now = date("Ymd-His");
        $tables = $this->getTables();
        if ($tableName) {
            $tables = array_filter($tables, function ($item) use ($tableName) {
                //print_r($arItem);
                return $item["table_name"] === $tableName;
            });
        }
        //die;
        //echo "<pre>"; print_r($arTables);die;
        //$arLines = ["/*database $sNow*/\n USE {$this->arConn["database"]}-x"];
        //$arLines = ["/*database $sNow*/\n USE db_killme"];

        foreach($tables as $table) {
            //vistas
            if ($table["otype"] == "v") {
                continue;
            }
            $tableName = $table["table_name"];

            if ($isDelete) {
                $lines[] = " DELETE FROM `$tableName`;";
            }

            $fields = $this->getFieldsInfo($tableName, 1);
            //print_r($arFields);die;
            $selects = [];
            foreach($fields as $field) {
                $fieldType = $field["field_type"];
                if (in_array($fieldType, $this->binary)) {
                    continue;
                }
                $selects[] = $field["field_name"];
            }

            $orderBy = "1";
            if (in_array("id", $selects)) {
                $orderBy = "id";
            }
            if (in_array("idn", $selects)) {
                $orderBy = "idn";
            }

            $select = implode("],[", $selects);
            $select = "[$select]";

            $selectMy = implode("`,`", $selects);
            $selectMy = "`$selectMy`";

            $ql = "SELECT $select FROM $tableName ORDER BY $orderBy ASC";
            //print_r($sSQL);die;
            $rowsArray = $this->db->query($ql);
            if ($rowsArray) {
                //$arRows = array_unique($arRows);
                $rows = count($rowsArray);
                //$arLines[] = "INSERT INTO `$sTableName` ($sSelectMy) VALUES ";

                foreach($rowsArray as $i => $row) {
                    $ins = [];
                    foreach($selects as $fieldName) {
                        $fieldType = $this->getFieldtype($fieldName, $fields);
                        $value = $row[$fieldName];

                        if (in_array($value, ["",null])) {
                            if ($this->isNullable($fieldName, $fields)
                               || in_array($fieldType, $this->numeric)
                               || in_array($fieldType, $this->date)) {
                                $value = "NULL";
                            } else {
                                $value = "''";
                            }
                        } else {
                            $value = str_replace("'", "\\'", $value);
                            if (!in_array($fieldType, $this->numeric)) {
                                $value = "'$value'";
                            }
                        }

                        $ins[] = $value;
                    }
                    $insert = "INSERT INTO `$tableName` ($selectMy) VALUES(";
                    $insert .= implode(",", $ins);
                    $insert .= ");";

                    //if (($iRows-1)===$i) $sInsert .= ";";
                    //else $sInsert .= ",";

                    $lines[] = $insert;
                }//foreach arRows
            }//if arRows
            else {
                $lines[] = "-- no rows for $tableName";
            }
        }//foreach tables
        $insert = implode("\n", $lines);
        return $insert;
    }//get_insert_bulk_mysql

    public function getCreateTable($tableName, $isDrop = 1)
    {
        switch($this->motorTo) {
            case "mysql":
                return $this->getCreateTableMysql($tableName, $isDrop);
            case "sqlite":
                return $this->getCreateTableSelf($tableName, $isDrop);
            default:
                return $this->getCreateTableSelf($tableName, $isDrop);
        }
    }//get_create_table

    private function getCreateTableSelf($tableName, $isDrop = 1)
    {
        $fields = $this->getFieldsInfo($tableName);
        if ($fields) {
            $sqls = [];
            if ($isDrop) {
                $sqls[] = "IF (OBJECT_ID('$tableName', 'U') IS NOT NULL) DROP TABLE dbo.$tableName ";
            }

            $sqls[] = "CREATE TABLE [$tableName] (";

            $qLf = [];
            $pks = $this->getPks($fields);

            foreach($fields as $fld) {
                $default = "";
                $pk = "NULL";

                $fieldDef = $fld["defvalue"];
                $fieldName = $fld["field_name"];
                $fieldType = $fld["field_type"];
                $fieldLen = "({$fld["field_length"]})";
                if (in_array($fieldType, $this->noLen)) {
                    $fieldLen = "";
                }

                //trato el default
                $defKey = strtolower($tableName)."_".strtolower($fieldName);
                if ($fieldDef !== "NULL" && strlen($fieldDef) < 20) {
                    $default = "CONSTRAINT [DF_$defKey] DEFAULT ($fieldDef)";
                }

                if ($fld["ispk"]) {
                    $pk = "";
                }

                $qLf[] = "[$fieldName] [$fieldType]$fieldLen $pk $default";
            }//foreach

            $sqls[] = implode(",\n", $qLf);
            if ($pks) {
                $tableLow = strtolower($tableName);
                $sqls[] = ",CONSTRAINT [PK_{$tableLow}] PRIMARY KEY CLUSTERED (";

                $tmp = [];
                foreach($pks as $fieldName) {
                    $tmp[] = "[$fieldName] ASC";
                }

                $sqls[] = implode(",", $tmp).") ";
                $sqls[] = "WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON) ON [PRIMARY]";
            }
            $sqls[] = ") ON [PRIMARY]";
            $sql = implode("\n", $sqls);
            return $sql;
        } else {
            $this->addError("get_create_table tabla:$tableName sin campos");
        }

        return "";
    }//get_create_table_self

    private function getCreateTableMysql($tableName, $isDrop = 1)
    {
        $fields = $this->getFieldsInfo($tableName);

        if ($fields) {
            $sqls = [];
            if ($isDrop) {
                $sqls[] = "DROP TABLE IF EXISTS `$tableName`;";
            }

            $sqls[] = "CREATE TABLE `$tableName` (";

            //sentencias para campos
            $qLf = [];
            $pks = $this->getPks($fields);

            foreach($fields as $i => $fld) {
                $default = "";
                $pk = "NULL";

                $fieldDef = $fld["defvalue"];
                $fieldName = $fld["field_name"];
                $fieldType = $fld["field_type"];
                $fieldLen = $fld["field_length"];
                $fieldTypeTo = $this->getFieldtypeMap($fieldType, "mssql", "mysql");
                if (is_array($fieldTypeTo)) {
                    pr("Error traduccion campo: table:$tableName,name:$fieldName,type:$fieldType,order:$i");
                    die;
                }
                $fieldLen = "($fieldLen)";
                if (in_array($fieldType, $this->noLen)) {
                    $fieldLen = "";
                }

                if ($fieldDef !== "NULL" && strlen($fieldDef) < 20) {
                    $default = "DEFAULT $fieldDef";
                }

                if ($fld["ispk"]) {
                    $pk = "NOT NULL";
                    $default = "";
                }

                //todo si el default lleva comillas hay que tratarlo
                $qLf[] = "`$fieldName` $fieldTypeTo$fieldLen $pk $default";
            }//foreach

            $sqls[] = implode(",\n", $qLf);
            if ($pks) {
                $sqls[] = ",PRIMARY KEY (`".implode("`,`", $pks)."`)";
            }
            $sqls[] = ");";
            $sql = implode("\n", $sqls);
            return $sql;
        } else {
            $this->addError("get_create_table_mysql tabla:$tableName sin campos");
        }
        return "";
    }//get_create_table_mysql

    public function getSchema($asString = 1)
    {
        switch($this->motorTo) {
            case "mysql":
                return $this->getSchemaMysql($asString);
                break;
            case "sqlite":
                return $this->getSchemaSelf($asString);
                break;
            default:
                return $this->getSchemaSelf($asString);
                break;
        }
    }//get_schema

    public function getSchemaSelf($asString = 1)
    {
        $now = date("Ymd-His");
        $tables = $this->getTables();

        $lines = ["/*database $now*/\n USE {$this->conn["database"]}-x"];
        foreach($tables as $table) {
            if ($table["otype"] == "u") {
                $lines[] = $this->getCreateTable($table["table_name"]);
            }
        }

        if ($asString) {
            return implode("\n/* -- end table -- */\n", $lines);
        }
        return $lines;
    }//get_schema_self

    private function getSchemaMysql($asString = 1)
    {
        $now = date("Ymd-His");
        $tables = $this->getTables();

        $lines = ["/*database $now*/\n USE {$this->conn["database"]}-x;"];
        foreach($tables as $table) {
            if ($table["otype"] == "u") {
                $lines[] = $this->getCreateTable($table["table_name"]);
            }
        }

        if ($asString) {
            return implode("\n/* -- end table -- */\n", $lines);
        }
        return $lines;
    }//get_schema_mysql

    private function getPks($fields)
    {
        $return = [];
        foreach($fields as $field) {
            if ($field["ispk"]) {
                $return[] = $field["field_name"];
            }
        }
        return $return;
    }//get_pks

    private function getFieldtype($fieldName, $fields = [])
    {
        foreach($fields as $field) {
            if ($field["field_name"] == $fieldName) {
                return $field["field_type"];
            }
        }
        return "";
    }//get_fieldtype

    private function isNullable($fieldName, $fields = [])
    {
        foreach($fields as $field) {
            if ($field["field_name"] == $fieldName) {
                return $field["is_nullable"];
            }
        }
        return 0;
    }//get_fieldtype

    public function getNumrows($tableName = "")
    {
        if ($tableName) {
            $tableName = "AND TABLE_NAME = '$tableName'";
        }
        $ql = "-- get_numrows
        SELECT t.*,r.irows
        FROM
        (
            SELECT OBJECT_ID as objid
            ,OBJECT_NAME(OBJECT_ID) AS tablename
            ,SUM(row_count) AS irows
            FROM sys.dm_db_partition_stats
            WHERE index_id = 0/*heap*/ 
            OR index_id = 1/*clustered index*/
            GROUP BY OBJECT_ID
        ) AS r
        INNER JOIN
        (
            SELECT DISTINCT 
            TABLE_CATALOG AS db
            ,TABLE_NAME AS tablename
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE 1=1
            AND TABLE_CATALOG='{$this->conn["database"]}'
            $tableName
        ) AS t
        ON r.tablename = t.tablename            
        ";
        $this->log($ql, "get_numrows");
        $rows = $this->db->query($ql);
        return $rows;
    }//get_numrows

    public function getNotnullFields($tableName = "")
    {
        $notNull = [];
        if ($tableName) {
            $ql = "-- get_notnull_fields
            SELECT * FROM $tableName
            ";
            $rows = $this->db->query($ql);
            if ($rows) {
                $notNull[$tableName] = [];
                $fieldsArray = array_keys($rows[0]);
                foreach($fieldsArray as $fieldName) {
                    //los valores de la columna
                    $tmp = array_column($rows, $fieldName);
                    $dists = array_unique($tmp);
                    //pr($arDist);
                    $dist = count($dists);
                    if ($dist === 0) {
                        continue;
                    } elseif ($dist === 1) {
                        //pr($arDist);die;
                        $k = array_keys($dists);
                        $k = $k[0];
                        if (in_array($dists[$k], ["",null,"0",0])) {
                            continue;
                        } else {
                            $notNull[$tableName][] = ["field" => $fieldName,"distinct" => $dist];
                        }
                    } else {
                        $notNull[$tableName][] = ["field" => $fieldName,"distinct" => $dist];
                    }
                }//foreach(arFields)
            }//if (arRows)
        }
        if (isset($notNull[$tableName]) && count($notNull[$tableName])) {
            $fieldsArray = $notNull[$tableName];
            usort($fieldsArray, function ($a, $b) {
                return $a["distinct"] < $b["distinct"];
            });
            //pr($arFields);
            $fields = implode(",\n", array_column($fieldsArray, "field"));
            $ql = " -- get_notnull_fields
            SELECT $fields 
            FROM $tableName";
            $notNull[$tableName]["sql"] = $ql;
        }
        return $notNull;
    }//get_numrows

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

    /**
     * El formato de destino. En caso de querer exportar para mysql o sqlite
     * @param string $value mysql,sqlite
     */
    public function setMotor($value)
    {
        $this->motorTo = $value;
    }
}//ComponentMssqlExport
