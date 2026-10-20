<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.1
 * @name HelperUlLi
 * @date 10-04-2013 14:33 (SPAIN)
* @file HelperUlLi.php
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperUlLi extends TheFrameworkHelper
{
    public function __construct($id = "", $innerhtml = "")
    {
        $this->_idprefix = "li";
        $this->_type = "li";
        $this->_id = $id;
        $this->_inner_html = $innerhtml;
    }

    //li
    public function getHtml()
    {
        $htmlToReturn = "";
        if($this->_comments) {
            $htmlToReturn .= "<!-- $this->_comments -->\n";
        }
        $htmlToReturn .= $this->getOpentag();
        //Agrega a inner_html los valores obtenidos con
        $this->loadInnerObjects();
        $htmlToReturn .= $this->_inner_html;
        $htmlToReturn .= $this->getClosetag();
        return $htmlToReturn;
    }

    public function getOpentag()
    {
        $htmlOpenTag = "<$this->_type";
        if($this->_id) {
            $htmlOpenTag .= " id=\"$this->_idprefix$this->_id\"";
        }
        //propiedades html5
        if($this->_isDisabled) {
            $htmlOpenTag .= " disabled";
        }
        if($this->_isReadOnly) {
            $htmlOpenTag .= " readonly";
        }
        if($this->_isRequired) {
            $htmlOpenTag .= " required";
        }
        //eventos
        if($this->_js_onblur) {
            $htmlOpenTag .= " onblur=\"$this->_js_onblur\"";
        }
        if($this->_js_onchange) {
            $htmlOpenTag .= " onchange=\"$this->_js_onchange\"";
        }
        if($this->_js_onclick) {
            $htmlOpenTag .= " onclick=\"$this->_js_onclick\"";
        }
        if($this->_js_onkeypress) {
            $htmlOpenTag .= " onkeypress=\"$this->_js_onkeypress\"";
        }
        if($this->_js_onfocus) {
            $htmlOpenTag .= " onfocus=\"$this->_js_onfocus\"";
        }
        if($this->_js_onmouseover) {
            $htmlOpenTag .= " onmouseover=\"$this->_js_onmouseover\"";
        }
        if($this->_js_onmouseout) {
            $htmlOpenTag .= " onmouseout=\"$this->_js_onmouseout\"";
        }

        //aspecto
        $this->loadCssclass();
        if($this->_class) {
            $htmlOpenTag .= " class=\"$this->_class\"";
        }
        $this->loadStyle();
        if($this->_style) {
            $htmlOpenTag .= " style=\"$this->_style\"";
        }
        //atributos extras
        if($this->extras) {
            $htmlOpenTag .= " ".$this->getExtras();
        }
        $htmlOpenTag .= ">\n";
        return $htmlOpenTag;
    }

    //**********************************
    //             SETS
    //**********************************
    //public function set_array_items($arItems=array()){$this->_arItems = $arItems;}

    //**********************************
    //             GETS
    //**********************************
    //public function get_array_li(){return $this->_arItems;}
}
