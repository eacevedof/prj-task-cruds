<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.5
 * @name HelperImage
 * @date 06-06-2014 10:2
 * @file HelperImage.php
 * @observations:
 * @requires:
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperImage extends TheFrameworkHelper
{
    protected $_src;
    protected $_alt;
    protected $_title;

    public function __construct($src = "", $id = "", $class = "", $style = "", $extras = array())
    {
        $this->_type = "img";
        $this->_idprefix = "";
        $this->_id = $id;

        $this->_src = $src;
        if($class) {
            $this->classes[] = $class;
        }
        if($style) {
            $this->styles[] = $style;
        }
        $this->extras = $extras;
    }

    //Fieldset
    public function getHtml()
    {
        $htmlToReturn = "<$this->_type";
        if($this->_src) {
            $htmlToReturn .= " src=\"$this->_src\"";
        }
        if($this->_alt) {
            $htmlToReturn .= " alt=\"$this->_alt\"";
        }
        if($this->_title) {
            $htmlToReturn .= " title=\"$this->_title\"";
        }
        if($this->_id) {
            $htmlToReturn .= " id=\"$this->_idprefix$this->_id\"";
        }
        //eventos
        if($this->_js_onblur) {
            $htmlToReturn .= " onblur=\"$this->_js_onblur\"";
        }
        if($this->_js_onchange) {
            $htmlToReturn .= " onchange=\"$this->_js_onchange\"";
        }
        if($this->_js_onclick) {
            $htmlToReturn .= " onclick=\"$this->_js_onclick\"";
        }
        if($this->_js_onkeypress) {
            $htmlToReturn .= " onkeypress=\"$this->_js_onkeypress\"";
        }
        if($this->_js_onfocus) {
            $htmlToReturn .= " onfocus=\"$this->_js_onfocus\"";
        }
        if($this->_js_onmouseover) {
            $htmlToReturn .= " onmouseover=\"$this->_js_onmouseover\"";
        }
        if($this->_js_onmouseout) {
            $htmlToReturn .= " onmouseout=\"$this->_js_onmouseout\"";
        }
        //aspecto
        $this->loadCssclass();
        if($this->_class) {
            $htmlToReturn .= " class=\"$this->_class\"";
        }
        $this->loadStyle();
        if($this->_style) {
            $htmlToReturn .= " style=\"$this->_style\"";
        }
        //atributos extra
        if($this->_attr_dbfield) {
            $htmlToReturn .= " dbfield=\"$this->_attr_dbfield\"";
        }
        if($this->_attr_dbtype) {
            $htmlToReturn .= " dbtype=\"$this->_attr_dbtype\"";
        }
        if($this->extras) {
            $htmlToReturn .= " ".$this->getExtras();
        }
        //if($this->_isPrimaryKey) $sHtmlOpenTag .= " pk=\"pk\"";
        //if($this->_attr_dbtype) $sHtmlOpenTag .= " dbtype=\"$this->_attr_dbtype\"";
        $htmlToReturn .= ">";
        return $htmlToReturn;
    }


    //**********************************
    //             SETS
    //**********************************
    public function setSrc($url)
    {
        $this->_src = $url;
    }
    public function setAlt($value)
    {
        $this->_alt = $value;
    }
    public function setTitle($value)
    {
        $this->_title = $value;
    }
    //**********************************
    //             GETS
    //**********************************

    //**********************************
    //           MAKE PUBLIC
    //**********************************
    public function showOpentag()
    {
        parent::showOpentag();
    }
    public function showClosetag()
    {
        parent::showClosetag();
    }

}
