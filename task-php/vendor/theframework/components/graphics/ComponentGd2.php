<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @name ComponentGd2
 * @file component_gd2.php
 * @version 1.1.0
 * @date 31-03-2018 17:41
 * @observations
 */

namespace TheFramework\Components;

class ComponentGd2
{
    private $from;
    private $to;
    private $isError;
    private $errors;

    //$GLOBALS["config_app_dir"].$GLOBALS["config_web_folder"].config_bar.$GLOBALS["config_res_dir"].config_bar."products_picture".config_bar.$Nom_Photo
    public function __construct()
    {
        $this->defineResdir();
        $this->isError = false;
        $this->errors = array();
        $this->from = array("pathfolder" => PATH_RESDIR."/products_picture/","filename" => "");
        $this->tmp = array();
        $this->to = array("pathfolder" => PATH_RESDIR."/products_picture/","filename" => "");
    }

    private function defineResdir()
    {
        if (!defined("PATH_RESDIR")) {
            define("PATH_RESDIR", realpath($GLOBALS["config_app_dir"].$GLOBALS["config_web_folder"]."/".$GLOBALS["config_res_dir"]));
        }
    }

    private function getType($filename)
    {
        return end(explode(".", trim($filename)));
    }

    private function getImageObj($extension, $pathFile)
    {
        switch($extension) {
            case "png": $image = imagecreatefrompng($pathFile);
                break;
            case "gif": $image = imagecreatefromgif($pathFile);
                break;
            case "bmp": $image = imagecreatefromwbmp($pathFile);
                break;
            default:
                $image = imagecreatefromjpeg($pathFile);
        }//switch(extension)
        return $image;
    }//get_image_obj

    private function getImageBlankObj($w, $h)
    {
        $image = imagecreatetruecolor($w, $h);
        return $image;
    }//get_image_blank_obj

    /**
     * a partir de dos objetos, uno en blanco (lienzo) y otro original ($arFrom[object]) se copia el original en el blanco
     * @param array $arFrom array("object","x","y","w","h")
     * @param array $arTo array("object","x","y","w","h")
     * @return boolean
     */
    private function saveInBlank($from, $to)
    {
        //imagecopyresampled($dst_image, $src_image, int $dst_x, int $dst_y, int $src_x, int $src_y, int $dst_w, int $dst_h, int $src_w, int $src_h): bool {}
        //imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
        //imagecopyresampled($tnImage,$fullImage, 0,$isy, 0,0, $ix,$iy, $fullSize[0],$fullSize[1]);
        return imagecopyresampled($to["object"], $from["object"], $to["x"], $to["y"], $from["x"], $from["y"], $to["w"], $to["h"], $from["w"], $from["h"]);
    }//save_in_blank

    public function getSize($pathFile)
    {
        //getimagesize("https://www.virginexperiencedays.co.uk/content/img/product/large/big-cat-encounter--17120907.jpg");
        /*array(7) {[0]=>int(1200) [1]=> int(800) [2]=> int(2) [3]=> string(25) "width="1200" height="800"" ["bits"]=> int(8)
        ["channels"]=>int(3) ["mime"]=> string(10) "image/jpeg"}*/
        $size = getimagesize($pathFile);
        return array("w" => $size[0],"h" => $size[1]);
    }//get_size

    /**
     * w: El ancho en el que se desea transformar la imagen original
     * h: La altura que tendrá la imagen original.
     * Son excluyentes, en caso de pasar los dos valores se tomara unicamente w
     * @param array $arTo array("w","h")
     */
    public function resize($to)
    {
        $w = isset($to["w"]) ? $to["w"] : null;
        $h = isset($to["h"]) ? $to["h"] : null;
        $this->from["pathfile"] = $this->from["pathfolder"]."/".$this->from["filename"];
        $this->to["pathfile"] = $this->from["pathfolder"]."/".$this->from["filename"];

        $pathFileFrom = $this->from["pathfile"];
        if (!$pathFileFrom) {
            $this->addError("Ruta de origen no proporcionada");
        }
        if (!$this->to["pathfile"]) {
            $this->addError("Ruta de destino no proporcionada");
        }
        if (!is_file($pathFileFrom)) {
            $this->addError("Archivo no encontrado en $pathFileFrom");
        }

        if (($w || $h) && !$this->isError) {
            $ext = $this->getType($this->from["filename"]);
            $imgFrom = $this->getImageObj($ext, $pathFileFrom);
            $size = $this->getSize($pathFileFrom);

            if ($w) {
                $h = floor($size["h"] / $size["w"] * $w);
            } elseif ($h) {
                $w = floor($size["h"] / $size["w"] * $h);
            }

            $imgBlank = $this->getImageBlankObj($w, $h);
            $from = array("object" => $imgFrom,"x" => 0,"y" => 0,"w" => $size["w"],"h" => $size["h"]);
            $to = array("object" => $imgBlank,"x" => 0,"y" => 0,"w" => $w,"h" => $h);
            //se guarda en el lienzo en blanco
            $isPrinted = $this->saveInBlank($from, $to);
            if ($isPrinted) {
                imagejpeg($to["object"], $this->to["pathfile"]);
                imagedestroy($imgFrom);
                imagedestroy($imgBlank);
            } else {
                $this->addError("Ocurrio un error al guardar en blanco: arFrom:".var_export($from, 1)." arTo:".var_export($to, 1));
            }
        } else {
            $this->addError("No hay datos de destino:".var_export($to, 1));
        }

    }//resize

    private function addError($message)
    {
        $this->isError = true;
        $this->errors[] = $message;
    }
    public function addFrom($key, $value)
    {
        $this->from[$key] = $value;
    }
    public function addTo($key, $value)
    {
        $this->to[$key] = $value;
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
}//class ComponentGd2
