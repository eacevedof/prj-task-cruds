<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.1.1
 * @name HelperButtonBasic
 * @file HelperButtonBasic.php
 * @date 24-12-2016 11:28
 * @observations
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperButtonBasic extends TheFrameworkHelper
{
    protected $icon;

    public function __construct($id = "", $innerhtml = "")
    {
        //tiene que ser button sino hay tipo ejecuta un submit
        $this->_type = "button";
        $this->_idprefix = "";
        $this->_id = $id;
        $this->_inner_html = $innerhtml;
    }

    public function getHtml()
    {
        $htmlToReturn = "";
        if($this->_comments) {
            $htmlToReturn = "<!-- $this->_comments -->\n";
        }
        $htmlToReturn .= $this->getOpentag();
        //Agrega a inner_html los valores obtenidos con
        //$this->load_inner_objects(); A un boton no se le puede pasar objetos embebidos
        if($this->icon) {
            $htmlToReturn .= "<span class=\"$this->icon\"> </span> ";
        }
        $htmlToReturn .= $this->_inner_html;
        $htmlToReturn .= "</button>";
        return $htmlToReturn;
    }

    public function getOpentag()
    {
        $htmlToReturn = "<button";
        if($this->_type) {
            $htmlToReturn .= " type=\"$this->_type\"";
        }
        if($this->_id) {
            $htmlToReturn .= " id=\"$this->_idprefix$this->_id\"";
        }
        if($this->_isDisabled) {
            $htmlToReturn .= " disabled";
        }

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

        $htmlToReturn .= ">\n";
        return $htmlToReturn;
    }

    public function setIcon($class)
    {
        $this->icon = $class;
    }
}
