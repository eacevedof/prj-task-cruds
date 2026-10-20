<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.2
 * @name HelperFpdfCell
 * @date 01-06-2014 12:45
 * @file HelperFpdfCell.php
 * @observations
 *      requires: FPDF
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperFpdfCell extends TheFrameworkHelper
{
    protected $isSingle;//=FALSE;//single,multi

    //cell params
    protected $width;
    protected $heigth;
    protected $text;
    //protected $isTextUTF;
    protected $border;
    protected $numNL;
    protected $align;
    protected $isFill;
    protected $urlPageLink;

    //Localizacion
    protected $x;
    protected $y;

    //Estilos
    protected $fontColor;
    protected $fontSize;
    protected $fontStyle;
    protected $backColor;
    protected $isResetColors;

    /**
     * Por defecto crea tipo multiline
     * @param boolean $isSingle
     */
    public function __construct($isSingle = false)
    {
        //$this->Cell($iAncho,$iAltura,$sTitulo,$iAnchoBorde,$iSaltosLinea,$cAlineacion,fondo?TRUE|FALSE,??);
        //$w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link=''
        $this->isSingle = $isSingle;
        $this->width = 1;
        $this->heigth = 1;
        $this->text = "";
        $this->border = 0;
        $this->numNL = 0;
        $this->isFill = false;
        $this->urlPageLink = "";
        //$this->isTextUTF = TRUE;
        //MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false)
    }

    //==================================
    //             SETS
    //==================================
    public function setYByheight()
    {
    }

    public function setSingle($isOn = true)
    {
        $this->isSingle = $isOn;
    }
    public function setWidth($value)
    {
        $this->width = $value;
    }
    public function setHeight($value)
    {
        $this->heigth = $value;
    }
    public function setText($value)
    {
        $this->text = $value;
    }
    public function setBorder($width)
    {
        $this->border = $width;
    }
    public function setNumlineUnit($unit)
    {
        $this->numNL = $unit;
    }
    /**
     * Right, Center, Left, J??
     * @param char $cAlign R|C|J
     */
    public function setTypeAlign($align)
    {
        $this->align = $align;
    }
    public function setUsefill($isOn = true)
    {
        $this->isFill = $isOn;
    }
    public function setPagelink($value)
    {
        $this->urlPageLink = $value;
    }

    public function setX($x)
    {
        $this->x = $x;
    }
    public function setY($y)
    {
        $this->y = $y;
    }

    public function setFont($value)
    {
        $this->font = $value;
    }
    /**
     * @param string $sValue B|IB|U
     */
    public function setFontstyle($value)
    {
        $this->fontStyle = $value;
    }
    public function setFontsize($size)
    {
        $this->fontSize = $size;
    }
    public function setFontcolor($value)
    {
        $this->fontColor = $value;
    }
    public function setBackcolor($value)
    {
        $this->backColor = $value;
    }
    public function setResetcolors($isOn = true)
    {
        $this->isResetColors = $isOn;
    }
    //public function set_utf8($isOn=TRUE){$this->isTextUTF=$isOn;}

    //==================================
    //             GETS
    //==================================
    public function isSingle()
    {
        return $this->isSingle;
    }
    public function getWidth()
    {
        return $this->width;
    }
    public function getHeight()
    {
        return $this->heigth;
    }
    public function getText()
    {
        return $this->text;
    }

    public function getBorder()
    {
        return $this->border;
    }
    public function getNumlineUnit()
    {
        return $this->numNL;
    }
    public function getTypeAlign()
    {
        return $this->align;
    }
    public function getUsefill()
    {
        return $this->isFill;
    }
    public function getPagelink()
    {
        return $this->urlPageLink;
    }

    public function getX()
    {
        return $this->x;
    }
    public function getY()
    {
        return $this->y;
    }

    public function getFont()
    {
        return $this->font;
    }
    public function getFontstyle()
    {
        return $this->fontStyle;
    }
    public function getFontsize()
    {
        return $this->fontSize;
    }
    public function getFontcolor()
    {
        return $this->fontColor;
    }
    public function getBackcolor()
    {
        return $this->backColor;
    }
    public function isResetcolors()
    {
        return $this->isResetColors;
    }

}
