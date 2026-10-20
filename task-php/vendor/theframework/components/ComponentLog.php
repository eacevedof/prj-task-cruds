<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @name TheApplication\Components\ComponentLog
 * @file ComponentLog.php 1.2.0
 * @date 30-11-2017 19:26 SPAIN
 * @observations
 */

namespace TheFramework\Components;

class ComponentLog
{
    public const DS = DIRECTORY_SEPARATOR;

    private $pathFolder;
    private $subfType;
    private $fileName;

    public function __construct($subfType = "", $pathFolder = "")
    {
        $this->pathFolder = $pathFolder;
        $this->subfType = $subfType;
        $this->fileName = "app_".date("Ymd").".log";
        if (!$pathFolder) {
            $this->pathFolder = __DIR__;
        }
        if (!$subfType) {
            $this->subfType = "debug";
        }
        //intenta crear la carpeta de logs
        $this->fixFolder();
    }

    private function fixFolder()
    {
        $logFolder = $this->pathFolder.self::DS
                        .$this->subfType.self::DS;
        if (!is_dir($logFolder)) {
            @mkdir($logFolder);
        }
    }

    private function merge($content, $title)
    {
        $ip = $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1";
        $return = "-- [".date("Ymd-His")." ip:$ip]\n";
        if ($title) {
            $return .= $title.":\n";
        }
        if ($content) {
            $return .= $content."\n\n";
        }
        return $return;
    }

    public function save($var, $title = null)
    {
        if (!is_string($var)) {
            $var = var_export($var, 1);
        }

        $pathFile = $this->pathFolder.self::DS
                        .$this->subfType.self::DS
                        .$this->fileName;

        if (is_file($pathFile)) {
            $cursor = fopen($pathFile, "a");
        } else {
            $cursor = fopen($pathFile, "x");
        }

        if ($cursor !== false) {
            $toSave = $this->merge($var, $title);
            fwrite($cursor, ""); //Grabo el caracter vacio
            if (!empty($toSave)) {
                fwrite($cursor, $toSave);
            }
            fclose($cursor); //cierro el archivo.
        } else {
            return false;
        }
        return true;
    }//save

    public function setFilename($value)
    {
        $this->fileName = "$value.log";
    }
    public function setSubfolder($value)
    {
        $this->subfType = "$value";
    }

}//ComponentLog
