<?php
/**
* @author Eduardo Acevedo Farje.
* @link eduardoaf.com
* @version 1.3.0
* @name ComponentDebug
* @file ComponentDebug.php
* @date 25-11-2018 16:14 (SPAIN)
* @observations: Para poder escribir en archivos se debe tener permisos de escritura y/o
* lectura para IUSR_<SERVERNAME>
*  load:24
* @requires: functions_utils.php v1.0.10
*/

namespace TheFramework\Components;

class ComponentDebug
{
    private static $_isSqlsOn = false;
    private static $_isMessagesOn = false;
    private static $_isPhpInfoOn = false;
    private static $_isIncludedOn = false;

    private static $messages = array();
    private static $sqls = array();
    private static $included = array();

    public static function config($isSqlsOn = false, $isMessagesOn = false, $isPhpInfoOn = false, $isIncludedOn = false)
    {
        self::$_isSqlsOn = $isSqlsOn;
        self::$_isMessagesOn = $isMessagesOn;
        self::$_isPhpInfoOn = $isPhpInfoOn;
        self::$_isIncludedOn = $isIncludedOn;
    }

    public static function setSql($sql, $count = 00, $time = "")
    {
        if (self::$_isSqlsOn) {
            self::$sqls[] = array("sql" => $sql,"count" => $count,"time" => $time);
        }
    }

    public static function setMessage($message, $title = "")
    {
        if (self::$_isMessagesOn) {
            self::$messages[] = array("Title" => $title,"Message" => $message);
        }
    }

    public static function getPhpInfo()
    {
        if (self::$_isPhpInfoOn) {
            return phpinfo();
        }
    }

    public static function getMessagesInArray()
    {
        if (self::$_isMessagesOn) {
            return self::$messages;
        }
    }

    public static function getMessagesInHtmlTable()
    {
        if (self::$_isMessagesOn && !self::isAjaxRequest()) {
            echo self::buildHtmlTable(self::$messages);
        }
    }

    public static function getSqlsInArray()
    {
        if (self::$_isSqlsOn) {
            return self::$sqls;
        }
    }

    public static function getSqlsInHtmlTable()
    {
        if (self::$_isSqlsOn) {
            $log = self::buildString(self::$sqls);
            self::log($log);
            if (!self::isAjaxRequest()) {
                echo self::buildHtmlTable(self::$sqls);
            }
        }
    }//get_sqls_in_html_table

    public static function setMessagesOn($isMessagesOn)
    {
        self::$_isMessagesOn = $isMessagesOn;
    }
    public static function setSqlsOn($isSqlsOn)
    {
        self::$_isSqlsOn = $isSqlsOn;
    }
    public static function setPhpInfoOn($isPhpInfoOn)
    {
        self::$_isPhpInfoOn = $isPhpInfoOn;
    }
    public static function isPhpInfoOn()
    {
        return self::$_isPhpInfoOn;
    }
    public static function isSqlsOn()
    {
        return self::$_isSqlsOn;
    }
    public static function isMessagesOn()
    {
        return self::$_isMessagesOn;
    }

    public static function isAjaxRequest()
    {
        //https://stackoverflow.com/questions/2579254/does-serverhttp-x-requested-with-exist-in-php-or-not
        $header = isset($_SERVER["HTTP_X_REQUESTED_WITH"]) ? $_SERVER["HTTP_X_REQUESTED_WITH"] : null;
        return ($header === "XMLHttpRequest");
    }

    private static function buildHtmlTrHeader($array = array())
    {
        $htmlTrHd = "";
        if (!empty($array)) {
            $htmlTrHd .= "<tr><th>Nยบ</th>\n";

            $row = $array[0];
            foreach($row as $title => $value) {
                $htmlTrHd .= "<th>$title</th>\n";
            }
            $htmlTrHd .= "</tr>\n";
        }
        return $htmlTrHd;
    }

    private static function getStyleTdBackground($row, $asError = 0)
    {
        if (!$asError) {
            if (($row % 2) == 0) {
                return "clsTrEven";
            } else {
                return "clsTrUneven";
            }
        }
        return "clsTrError";
    }

    private static function buildStyle()
    {
        $sytle = "<style type=\"text/css\">";
        $sytle .= "table#tblDebug
        {
            font-size:14px!important; 
            width:1000px;
            border-collapse: collapse!important;
            margin-bottom: 0;
            overflow:auto;
            font-weight:normal; 
            font-family: 'Courier New', Courier, monospace !important;
        }
        table#tblDebug tr td 
        {
            border: 1px solid black;
            padding-bottom: 0;
        }
        table#tblDebug tr td pre
        {
            line-height:normal!important;
            padding:0;
            margin:0;
            border:0;
        }
        table#tblDebug tr th
        {
            border:1px solid black;
            background-color: #F29A02;
            color:black;
        }
        .clsTrEven
        {
            background-color: #e9eaeb;
            color: black;
        }

        .clsTrUneven
        {
            background-color: #ECF71D;
            color: black;
        }

        .clsTrError
        {
            background-color: #F92104;
            color: white;
        }
        .clsTrList
        {
            background-color: #00CE41;/*verde*/
            color: white;
        }
        .clsTrHighlight
        {
            background-color: #55A9FC;/*celeste*/
            color: white;
        }
        .clsTrSession
        {
            background-color: #554455;/*not used*/
            color: white;
        }
        ";
        $sytle .= "</style>";
        return $sytle;
    }

    private static function buildHtmlTable($array = array())
    {
        if (isset($_SESSION["componentdebug"]) && is_array($_SESSION["componentdebug"])) {
            //bug($_SESSION["componentdebug"]);
            $array = array_merge($_SESSION["componentdebug"], $array);
            $_SESSION["componentdebug"] = null;
        }
        if (isset($_POST["componentdebug"]) && is_array($_POST["componentdebug"])) {
            $array = array_merge($_POST["componentdebug"], $array);
            $_POST["componentdebug"] = null;
        }

        $htmlTable = "";
        if (!empty($array)) {
            $htmlTable .= self::buildStyle();
            $htmlTable .= "<table id=\"tblDebug\">\n";
            $htmlTable .= self::buildHtmlTrHeader($array);
            foreach($array as $row => $rows) {
                $isError = 0;
                //bug($arRow);
                if (isset($rows["count"]) && $rows["count"] == "-1") {
                    $isError = 1;
                    $rows["count"] = "ERROR";
                }
                $tdStyle = self::getStyleTdBackground($row, $isError);
                $htmlTable .= "<tr>\n";
                $htmlTable .= "<td class=\"$tdStyle\" >$row</td>\n";
                foreach($rows as $fieldValue) {
                    //si se aplican tags de html se mejora la visibilidad de las consultas y se puede copiar y pegar
                    //respetando los saltos de linea ya que los br los pasa el portapapeles o el navegador a salto de linea simple \n
                    //el problema es que si se quiera recuperar la consulta del html generado los tags fastidian la consulta.
                    $fieldValue = str_replace("SELECT ", "<b>SELECT </b>", $fieldValue);
                    $fieldValue = str_replace(" AS ", "<b> AS </b>", $fieldValue);
                    $fieldValue = str_replace("FROM", "<br/><b>FROM</b>", $fieldValue);
                    $fieldValue = str_replace("WHERE ", "<br/><b>WHERE </b>", $fieldValue);
                    $fieldValue = str_replace("INNER JOIN", "<br/><b>INNER JOIN </b>", $fieldValue);
                    $fieldValue = str_replace("LEFT JOIN", "<br/><b>LEFT JOIN </b>", $fieldValue);
                    $fieldValue = str_replace(" AND ", "<br/><b> AND </b>", $fieldValue);
                    $fieldValue = str_replace(" OR ", "<br/><b> OR </b>", $fieldValue);
                    $fieldValue = str_replace(" IN ", "<b> IN </b>", $fieldValue);
                    $fieldValue = str_replace(" ON ", "<b> ON </b>", $fieldValue);
                    $fieldValue = str_replace(" NULL", "<b> NULL</b>", $fieldValue);
                    $fieldValue = str_replace("ORDER BY ", "<br/><b>ORDER BY </b>", $fieldValue);
                    $fieldValue = str_replace("GROUP BY ", "<br/><b>GROUP BY </b>", $fieldValue);
                    $fieldValue = str_replace("INSERT INTO ", "<b>INSERT INTO </b>", $fieldValue);
                    $fieldValue = str_replace("VALUES", "<br/><b>VALUES </b>", $fieldValue);

                    if (strstr($fieldValue, "UPDATE ")) {
                        $fieldValue = str_replace("UPDATE ", "<b>UPDATE </b>", $fieldValue);
                        $fieldValue = str_replace(",", "<br/>,", $fieldValue);
                        $fieldValue = str_replace("SET ", "<br/><b>SET </b>", $fieldValue);
                    }

                    $fieldValue = str_replace("DELETE FROM ", "<b>DELETE FROM </b>", $fieldValue);
                    if (!$isError) {
                        $substrings = array("get_select_","load_by","autoinsert","autoupdate","autoquarantine","autodelete");
                        if (isone_substring($substrings, $fieldValue)) {
                            $tdStyle = "clsTrList";
                        }
                        if (strstr($fieldValue, "%highlight%")) {
                            $tdStyle = "clsTrHighlight";
                        }
                    }

                    $htmlTable .= "<td class=\"$tdStyle\">$fieldValue</td>\n";
                }
                $htmlTable .= "</tr>\n";
            }
            $htmlTable .= "</table>\n";
        }
        return $htmlTable;
    }//build_html_table

    private static function buildString($array = array())
    {
        if (isset($_SESSION["componentdebug"]) && is_array($_SESSION["componentdebug"])) {
            //bug($_SESSION["componentdebug"]);
            $array = array_merge($_SESSION["componentdebug"], $array);
            $_SESSION["componentdebug"] = null;
        }
        if (isset($_POST["componentdebug"]) && is_array($_POST["componentdebug"])) {
            $array = array_merge($_POST["componentdebug"], $array);
            $_POST["componentdebug"] = null;
        }

        $log = "";
        if (!empty($array)) {
            foreach($array as $row => $rows) {
                foreach($rows as $fieldValue) {
                    $fieldValue = str_replace("\n\t\t\t", "\n", $fieldValue);
                    $fieldValue = str_replace("\n\t\t", "\n", $fieldValue);
                    $fieldValue = str_replace("\n\t", "\n", $fieldValue);
                    $fieldValue = str_replace("\n     ", "\n", $fieldValue);
                    $fieldValue = str_replace("\n    ", "\n", $fieldValue);
                    $fieldValue = str_replace("\n   ", "\n", $fieldValue);
                    $fieldValue = str_replace("\n  ", "\n", $fieldValue);
                    $fieldValue = str_replace("\n ", "\n", $fieldValue);
                    //$sFieldValue = str_replace("\t"," ",$sFieldValue);
                    $log .= "\n\n-- $row =>\n".trim($fieldValue);
                }//foreach arRow
            }//foreach arArray
        }
        return $log;
    }//build_string

    public static function log($var, $title = "")
    {
        $pathFolder = realpath($_SERVER["DOCUMENT_ROOT"]).DIRECTORY_SEPARATOR;
        $fileName = "componentdebug_".date("Ymd").".log";
        $pathFile = $pathFolder.$fileName;
        //pr($sPathFile);die;
        $value = $var;
        if (!is_string($value)) {
            $value = var_export($var, 1);
        }
        if ($title) {
            $value = "- [$title] -\n$value";
        }
        file_put_contents($pathFile, $value);
    }//log

}//ComponentDebug
