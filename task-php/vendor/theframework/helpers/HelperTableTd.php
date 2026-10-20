<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.3
 * @name HelperTableTd
 * @date 11-04-2013 14:12
 * @file HelperTableTd.php
 * @requires
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperTableTd extends TheFrameworkHelper
{
    private $_colspan = null;
    private $_isHeader = false;
    private $_attr_rownumber;
    private $_attr_colnumber;
    private $_attr_position;

    public function __construct($innerhtml = "", $id = "", $class = "", $style = "", $colspan = "", $extras = array())
    {
        $this->_type = "td";
        $this->_idprefix = "td";
        $this->_id = $id;

        $this->_inner_html = $innerhtml;
        $this->_colspan = $colspan;
        if($class) {
            $this->classes[] = $class;
        }
        if($style) {
            $this->styles[] = $style;
        }
        $this->extras = $extras;
    }

    public function getHtml()
    {
        $htmlToReturn = "";
        $htmlToReturn .= $this->getOpentag();
        //TODO puede que haya conflictos entre el add_inner_object y set_innerobject
        //Hacer pruebas
        $this->loadInnerObjects();
        //Tengo que poner "" porque si el valor es mostrar un 0 o un espacio no lo mostraría
        if($this->_inner_html !== "") {
            $htmlToReturn .= $this->_inner_html;
        }
        $htmlToReturn .= $this->getClosetag();
        return $htmlToReturn;
    }

    //**********************************
    //             SETS
    //**********************************
    public function setAttrRownumber($value)
    {
        $this->_attr_rownumber = $value;
    }
    public function setAttrColnumber($value)
    {
        $this->_attr_colnumber = $value;
    }
    public function setColspan($value)
    {
        $this->_colspan = $value;
    }
    public function setAsHeader($isOn = true)
    {
        $this->_isHeader = $isOn;
        if($this->_isHeader) {
            $this->_type = "th";
        } else {
            $this->_type = "td";
        }
    }

    public function setInnerobject($htmlObject)
    {
        //si es un array de objetos
        if(is_array($htmlObject)) {
            foreach($htmlObject as $html) {
                if(method_exists($html, "getHtml")) {
                    $this->_inner_html .= $html->getHtml();
                }
            }
        }
        //si es un objeto
        elseif(method_exists($htmlObject, "getHtml")) {
            $this->_inner_html .= $htmlObject->getHtml();
        } else {
            $this->_inner_html .= $htmlObject;
        }
    }

    public function setAttrPosition($numRow, $numColumn)
    {
        $this->_attr_position = $numRow."_".$numColumn;
    }
    //**********************************
    //             GETS
    //**********************************
    public function getOpentag()
    {
        $htmlToReturn = "<$this->_type";
        if($this->_id) {
            $htmlToReturn .= " id=\"$this->_idprefix$this->_id\"";
        }
        if($this->_colspan) {
            $htmlToReturn .= " colspan=\"$this->_colspan\"";
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
        //atributos extras pe. para usar el quryselector
        if($this->_attr_dbfield) {
            $htmlToReturn .= " dbfield=\"$this->_attr_dbfield\"";
        }
        if($this->_attr_dbtype) {
            $htmlToReturn .= " dbtype=\"$this->_attr_dbtype\"";
        }
        if($this->_isPrimaryKey) {
            $htmlToReturn .= " pk=\"pk\"";
        }
        if($this->_attr_colnumber !== "") {
            $htmlToReturn .= " colnumber=\"$this->_attr_colnumber\"";
        }
        if($this->_attr_rownumber !== "") {
            $htmlToReturn .= " rownumber=\"$this->_attr_rownumber\"";
        }
        if($this->_attr_position) {
            $htmlToReturn .= " cellpos=\"$this->_attr_position\"";
        }
        if($this->extras) {
            $htmlToReturn .= " ".$this->getExtras();
        }

        $htmlToReturn .= ">";
        return $htmlToReturn;
    }
    public function getClosetag()
    {
        return parent::getClosetag();
    }
    public function getColspan()
    {
        return $this->_colspan;
    }
    public function isHeader()
    {
        return $this->_isHeader;
    }
}
