<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @name TheApplication\Components\ComponentFile
 * @file ComponentFile.php 1.0.0
 * @date 12-03-2019 16:27 SPAIN
 * @observations
 */

namespace TheFramework\Components;

class ComponentFile
{
    private const DS = DIRECTORY_SEPARATOR;

    //private $sPathFolderFrom;
    private $pathFolderTo;
    private $fileNameTo;

    public function __construct($pathFolerTo = "", $fileNameTo = "", $pathFolderFrom = "")
    {
        //$this->sPathFolderFrom = $sPathFolderFrom;
        $this->pathFolderTo = $pathFolerTo;
        $this->fileNameTo = $fileNameTo;
        //if (!$sPathFolderFrom) $this->sPathFolderFrom = __DIR__;
        if (!$pathFolerTo) {
            $this->pathFolderTo = __DIR__;
        }
        if (!$fileNameTo) {
            $this->fileNameTo = "compofiletmp.txt";
        }
        //intenta crear la carpeta de logs
        $this->fixFolders();
    }

    private function fixFolders()
    {
        //$sLogFolder = $this->sPathFolderFrom.self::DS;
        //if (!is_dir($sLogFolder)) @mkdir($sLogFolder);
        $logFolder = $this->pathFolderTo.self::DS;
        if (!is_dir($logFolder)) {
            @mkdir($logFolder);
        }
    }

    public function save($content)
    {
        if (!is_string($content)) {
            $content = var_export($content, 1);
        }

        $pathFile = $this->pathFolderTo.self::DS
                     .$this->fileNameTo;

        if (is_file($pathFile)) {
            $cursor = fopen($pathFile, "a");
        } else {
            $cursor = fopen($pathFile, "x");
        }

        if ($cursor !== false) {
            $toSave = $content;
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



    public function setFilenameto($value)
    {
        $this->fileNameTo = $value;
    }
    public function setFolderto($value)
    {
        $this->pathFolderTo = $value;
    }
    public function saveBulkfile($data, $fieldSep = "@@@@", $lineSep = "####")
    {
        $lines = [];
        if (is_array($data)) {
            foreach ($data as $fields) {
                $lines[] = implode($fieldSep, $fields).$lineSep;
            }
        } else;
        //$this->add_error();

        $content = implode("\n", $lines);
        $this->save($content);
    }

}//ComponentFile
