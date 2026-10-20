<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.6
 * @name HelperLabel
 * @date 09-01-2017 12:55 (SPAIN)
 * @file HelperLabel.php
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperLabel extends TheFrameworkHelper
{
    private $_for = "";

    public function __construct(
        $for = "",
        $innerhtml = "",
        $id = "",
        $class = "",
        $style = "",
        $extras = array()
    ) {
        $this->_type = "label";
        $this->_idprefix = "";
        $this->_id = $id;

        $this->_inner_html = $innerhtml;
        $this->_for = $for;
        //$this->arClasses[] = "control-label";
        if($class) {
            $this->classes[] = $class;
        }
        if($style) {
            $this->styles[] = $style;
        }
        $this->extras = $extras;
    }

    //label
    public function getHtml()
    {
        $htmlToReturn = "";
        if($this->_comments) {
            $htmlToReturn = "<!-- $this->_comments -->\n";
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
        if($this->_for) {
            $htmlOpenTag .= " for=\"$this->_for\"";
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
        //atributos extras pe. para usar el quryselector
        if($this->_attr_dbfield) {
            $htmlOpenTag .= " dbfield=\"$this->_attr_dbfield\"";
        }
        if($this->_attr_dbtype) {
            $htmlOpenTag .= " dbtype=\"$this->_attr_dbtype\"";
        }
        if($this->_isPrimaryKey) {
            $htmlOpenTag .= " pk=\"pk\"";
        }
        if($this->extras) {
            $htmlOpenTag .= " ".$this->getExtras();
        }

        $htmlOpenTag .= ">";
        return $htmlOpenTag;
    }
    //**********************************
    //             SETS
    //**********************************
    public function setFor($value)
    {
        $this->_for = $value;
    }
    //**********************************
    //             GETS
    //**********************************
    public function getFor()
    {
        return $this->_for;
    }
}//HelperLabel
