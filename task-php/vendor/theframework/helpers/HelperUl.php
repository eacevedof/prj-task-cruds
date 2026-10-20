<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.2
 * @name HelperUl
 * @date 06-09-2013 16:58 (SPAIN)
 * @file HelperUl.php
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperUl extends TheFrameworkHelper
{
    protected $objLi;

    public function __construct($id, $innerhtml = "", $objLi = array())
    {
        $this->_idprefix = "";
        $this->_type = "ul";
        $this->_id = $id;
        $this->_inner_html = $innerhtml;
        $this->objLi = $objLi;
    }

    public function getHtml()
    {
        $htmlToReturn = "";
        if(!$this->_inner_html) {
            $this->_inner_html = $this->getArrayLiAsString();
        }
        if($this->_comments) {
            $htmlToReturn .= "<!-- $this->_comments -->\n";
        }
        $htmlToReturn .= $this->getOpentag();
        $htmlToReturn .= $this->_inner_html;
        $htmlToReturn .= $this->getClosetag();
        return $htmlToReturn;
    }

    private function getArrayLiAsString()
    {
        $li = "";
        foreach($this->objLi as $liObject) {
            if(is_object($liObject)) {
                $li .= $liObject->getHtml();
            } elseif(is_string($liObject)) {
                $li .= $l;
            }
        }

        return $li;
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
        //if($this->_isPrimaryKey) $sHtmlToReturn .= " pk=\"pk\"";
        //if($this->_attr_dbtype) $sHtmlToReturn .= " dbtype=\"$this->_attr_dbtype\"";
        $htmlOpenTag .= ">\n";
        return $htmlOpenTag;
    }

    //**********************************
    //             SETS
    //**********************************
    public function setArrayLi($objLi)
    {
        $this->objLi = $objLi;
    }


    //**********************************
    //             GETS
    //**********************************
    public function getArrayLi()
    {
        return $this->objLi;
    }
}
