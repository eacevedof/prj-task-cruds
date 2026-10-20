<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.6.0
 * @file functions_debug.php
 * @date 01-12-2018 17:57 (SPAIN)
 * @observations: Functions to print variables
 * @requires functions_string.php 1.0.2
 *  load:9
 */
//pr(__FILE__);// /var/www/wwwtheframework/the_framework/functions/functions_debug.php
//<editor-fold defaultstate="collapsed" desc="redundantes en index.php">
//hago esta comprobación para los casos en que theframework se use como mochila
if(!function_exists("errorson")) {
    //error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
    function errorson($type = "all")
    {
        error_reporting(0);
        ini_set("error_log", dirname(dirname(__FILE__))."/errorson.log");
        ini_set("display_errors", 1);
        switch ($type) {
            case "e":
            case "error":
                error_reporting(E_ERROR);
                break;

            case "w":
            case "warning":
                error_reporting(E_WARNING);
                break;

            case "p":
            case "parse":
                error_reporting(E_PARSE);
                break;

            case "n":
            case "notice":
                error_reporting(E_NOTICE);
                break;

            case "a":
            case "all":
                error_reporting(E_ALL);
                break;

                //no
            default:
                //desactivar toda notificación de error
                error_reporting(0);
                ini_set("display_errors", 0);
                break;
        }
    }//errorson
}

if(!function_exists("pr")) {
    function pr($var = "", $title = null)
    {
        if($title) {
            $title = " $title: ";
        }

        if(!is_string($var)) {
            $var = var_export($var, true);
        }
        #F1E087
        $tagPre = "<pre function=\"pr\" style=\"border:1px solid black;background:yellow; padding:0px; color:black; font-size:12px;\">\n";
        $tagFinPre = "</pre>\n";
        echo $tagPre.$title.$var.$tagFinPre;
    }//function pr
}

if(!function_exists("lg")) {
    function lg($var = "", $title = null)
    {
        $now = date("Y-m-d_H:i:s");
        if($title) {
            $title = "<<  $title >>";
        }
        $title = PHP_EOL."$now: $title";
        if(!is_string($var)) {
            $var = var_export($var, 1);
        }
        if($var) {
            $var = PHP_EOL.$var.PHP_EOL;
        }
        $var = $title.$var;

        $pathFile = "";
        if(defined("TFW_PATH_FOLDER_LOG")) {
            $pathFile .= TFW_PATH_FOLDER_LOG."/custom/";
        }
        $pathFile .= "tfwlog.log";

        $cursor = fopen($pathFile, "ab");
        $written = fwrite($cursor, $var);
        if($written === false) {
            fclose($cursor);
        }
        fclose($cursor);
    }//funtion lg
}
//</editor-fold>

function bug($var, $varName = "var", $isDie = false)
{
    if(IS_DEBUG_ALLOWED ||
       (isset($_SESSION["tfw_user_identificator"]) && ($_SESSION["tfw_user_identificator"] == -10 || $_SESSION["tfw_user_identificator"] == 1))) {
        if(is_string($var)) {
            $isSQL = false;
            $qLWords = array("select","from","inner join","insert into","update","delete");
            $tmpVar = strtolower($var);
            foreach($qLWords as $word) {
                //var_dump("word:$sWord, string:$sTmpVar",strpos($sWord,$sTmpVar));
                if(strpos($tmpVar, $word) !== false) {
                    $isSQL = true;
                    break;
                }
            }

            //var_dump($isSQL);
            if($isSQL) {
                if(!strpos($var, "\nFROM"));
                $var = str_replace("FROM", "\nFROM", $var);
                if(!strpos($var, "\nINNER"));
                $var = str_replace("INNER", "\nINNER", $var);
                if(!strpos($var, "\nLEFT"));
                $var = str_replace("LEFT", "\nLEFT", $var);
                if(!strpos($var, "\nRIGHT"));
                $var = str_replace("RIGHT", "\nRIGHT", $var);
                if(!strpos($var, "\nWHERE"));
                $var = str_replace("WHERE", "\nWHERE", $var);
                if(!strpos($var, "\nAND"));
                $var = str_replace("AND", "\nAND", $var);
                if(!strpos($var, "\nORDER BY"));
                $var = str_replace("ORDER BY", "\nORDER BY", $var);
            }
        }
        $tagPre = "<pre function=\"bug\" style=\"background:#CDE552; padding:0px; color:black; font-size:12px;\">\n";
        $tagFinPre = "</pre>\n";
        $nombreVariable = $tagPre ."VARIABLE <b>$varName</b>:";
        $nombreVariable .= $tagFinPre;
        echo $nombreVariable;
        echo  "<pre style=\" background:#E2EDA8; font-size:12px; padding-left:10px; text-align:left; color:black; font-weight:normal; font-family: \'Courier New\', Courier, monospace !important;\">\n";
        var_dump($var);
        echo  "</pre>";

        if($isDie) {
            die;
        }
    }
}//bug()

function bugpf($key)
{
    if($key == "") {
        $pG = array();
        $pG["FILES"] = $_FILES;
        bug($pG, "FILES");
    } else {
        bug($_FILES[$key], "\$_FILES[$key]");
    }
}//bugpf

function bugfileipath($filePath, $isDie = false)
{
    //if(is_firstchar($sFilePath,"/")||is_firstchar($sFilePath,"\\"))
    //remove_firstchar($sFilePath);
    //$sFilePath = DIRECTORY_SEPARATOR.$sFilePath;

    $paths = explode(PATH_SEPARATOR, get_include_path());
    foreach($paths as $dirPath) {
        $tmpPath = $dirPath.$filePath;
        //echo $sTmpPath."<br>";
        if(file_exists($tmpPath)) {
            bug(true, $tmpPath, $isDie);
            return;
        }
    }
    bug(false, $filePath, $isDie);
}//bugfileipath

function bugfile($filePath, $varName = "", $isDie = false)
{
    if(!$varName) {
        $varName = $filePath;
    }
    bug(is_file($filePath), $varName, $isDie);
}//bugfile

function bugdir($dirPath, $varName = "var", $isDie = false)
{
    bug(is_dir($dirPath), $varName, $isDie);
}//bugdir

function bugpg($title = "")
{
    $pG = array();
    $pG["POST"] = $_POST;
    $pG["GET"] = $_GET;
    $pG["FILES"] = $_FILES;
    bug($pG, "$title POST | GET | FILES");
}

function bugp($key = "")
{
    if($key == "") {
        $pG = array();
        $pG["POST"] = $_POST;
        bug($pG, "POST");
    } else {
        bug($_POST[$key], "POST[$key]");
    }
}

function bugg($key = "")
{
    if($key == "") {
        $pG = array();
        $pG["GET"] = $_GET;
        bug($pG, "GET");
    } else {
        bug($_GET[$key], "GET[$key]");
    }
}

function bugss($key = "")
{
    if($key == "") {
        $pG = array();
        $pG["session_id()"] = session_id();
        $pG["SESSION"] = $_SESSION;
        bug($pG, "SESSION");
    } else {
        bug($_SESSION[$key], "SESSION[$key]");
    }
}

function bugif($title = "")
{
    bug(get_included_files(), "$title included_files");
}

function bugversion()
{
    phpversion();
}

function bugsysinfo()
{
    $sysInfo = "DS: ".DIRECTORY_SEPARATOR." \n";
    $sysInfo .= "LIB EXTENSION: ".PHP_SHLIB_SUFFIX." \n";
    $sysInfo .= "PATH SEPARATOR: ".PATH_SEPARATOR." \n";
    $sysInfo .= "SERVER OS: ".php_uname("s")." \n";
    //echo  // \
    //echo "- LSUFIX: ".PHP_SHLIB_SUFFIX;    // dll
    //echo "- PATH SEP: ".PATH_SEPARATOR;      // ;
    // 's': Operating system name. eg. FreeBSD.
    //'n': Host name. eg. localhost.example.com.
    //echo php_uname();
    //echo PHP_OS;
    bug($sysInfo);
}

/**
 * Bug cookies
 */
function bugck()
{
    bug($_COOKIE, "cookie");
}

function bugipath($title = "")
{
    bug(explode(PATH_SEPARATOR, get_include_path().$title), "included path:");
}

function bugcond($var, $isCheckCondition)
{
    //var_dump($isCheckCondition);
    if($isCheckCondition) {
        bug($var);
    } else {
        pr("isCheckCondition = FALSE");
    }
}

function bugraw($var, $varName = null)
{
    $return = "\n";
    if($varName) {
        $return .= "$varName: \n";
    }

    $return .= var_export($var, 1);
    echo $return;
}

function bugf($key = "")
{
    if($key == "") {
        $pG = array();
        $pG["FILES"] = $_FILES;
        bug($pG, "FILES");
    } else {
        bug($_FILES[$key], "FILES[$key]");
    }
}

function bugex(Exception $ex, $title = "exception")
{
    bug("code:{$ex->getCode()},line:{$ex->getLine()},file:{$ex->getFile()},previous:{$ex->getPrevious()}", $title);
}

function bugconst()
{
    $consts = [
        "__CLASS__" => __CLASS__,
        "__DIR__" => __DIR__,
        "__FILE__" => __FILE__,
        "__FUNCTION__" => __FUNCTION__,
        "__LINE__" => __LINE__,
        "__METHOD__" => __METHOD__,
        "__METHOD__" => __METHOD__,
        "__NAMESPACE__" => __NAMESPACE__,
        "__TRAIT__" => __TRAIT__,
    ];
    bug($consts, "CONSTANTS");
}

function s($string)
{
    if(!is_string($string)) {
        $string = var_export($string, 1);
    }echo $string;
}
