<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 2.0.0
 * @name ComponentZip
 * @file ComponentZip.php
 * @date 30-06-2019 18:28 (SPAIN)
 * @observations
 *
 */
class ComponentZip
{
    protected $pathSource;
    protected $pathTarget;
    protected $folders;
    protected $files;
    protected $zipNames;

    public function __construct()
    {
        parent::__construct();
    }

    private function getNameTarget($posFolder)
    {
        $zipName = $this->zipNames[$posFolder];
        //si no hay se coge el primero
        if (!$zipName) {
            $zipName = $this->zipNames[0];
        }
        //si sigue sin haber se aplica por defecto
        if (!$zipName) {
            $zipName = "zipnoname";
        }

        if ($zipName != "zipnoname") {
            $zipName = $this->get_slug_cleaned($zipName, "_");
        }
        return $zipName;
    }

    public function compressFolders()
    {
        $this->fixPathTarget();
        if (is_dir($this->pathTarget)) {
            if (is_dir($this->pathSource)) {
                foreach($this->folders as $i => $folderName) {
                    $pathSoruce = $this->getPathSource($folderName);
                    if (is_dir($pathSoruce)) {
                        $zipName = $this->getNameTarget($i);
                        $zipPathTarget = $this->getPathTarget($zipName);
                        $this->compress($pathSoruce, $zipPathTarget);
                    } else {
                        $message = "Folder source $pathSoruce is not a directory";
                        $this->addError($message);
                    }
                }//foreach arFolders
            } else {
                $message = "Folder source $folderName is not a directory";
                $this->addError($message);
            }
        } else {
            $message = "Folder target $folderName is not a directory";
            $this->addError($message);
        }
    }

    private function fixPathTarget()
    {
        if (!$this->pathTarget && $this->pathSource) {
            $this->pathTarget = $this->pathSource;
        }
    }


    public function compress($pathSrcDirNoDS, $pathFileZip)
    {
        if ($cursor = opendir($pathSrcDirNoDS)) {
            $zipArchive = new ZipArchive();
            //ZipArchive::CREATE
            if ($zipArchive->open($pathFileZip, ZipArchive::CREATE) !== true) {
                $message = "Cannot open $pathFileZip";
                $this->addError($message);
                return;
            }

            //Recorro todo los archivos
            while(false !== ($fileName = readdir($cursor))) {
                $zipArchive->addFile($pathSrcDirNoDS.$fileName);
            }

            closedir($cursor);
            $this->add_debug("numfiles: $zipArchive->numFiles");
            $this->add_debug("status: $zipArchive->status");
            $zipArchive->close();
            $this->add_debug("Zip File: $pathFileZip");
        }
    }


    //==================================
    //             SETS
    //==================================
    public function setPathSource($value)
    {
        $this->pathSource = $this->get_fixed_syspath($value);
    }
    public function setPathTarget($value)
    {
        $this->pathTarget = $this->get_fixed_syspath($value);
    }

    public function setFolder($folderName)
    {
        $this->folders = array();
        if (trim($folderName)) {
            $this->folders[] = $folderName;
        }
    }
    public function addFolder($folderName)
    {
        if (trim($folderName)) {
            $this->folders[] = $folderName;
        }
    }
    public function setFiles($fileName)
    {
        $this->files = array();
        if (trim($fileName)) {
            $this->files[] = $fileName;
        }
    }
    public function addFiles($fileName)
    {
        if (trim($fileName)) {
            $this->files[] = $fileName;
        }
    }

    public function setZipname($fileName)
    {
        $this->zipNames = array();
        if (trim($fileName)) {
            $this->zipNames[] = $fileName;
        }
    }
    public function addZipname($fileName)
    {
        if (trim($fileName)) {
            $this->zipNames[] = $fileName;
        }
    }

    //==================================
    //             GETS
    //==================================
    private function getPathSource($fileFolder)
    {
        return $this->pathSource.DIRECTORY_SEPARATOR.$fileFolder;
    }
    private function getPathTarget($fileFolder)
    {
        return $this->pathTarget.DIRECTORY_SEPARATOR.$fileFolder;
    }
}
