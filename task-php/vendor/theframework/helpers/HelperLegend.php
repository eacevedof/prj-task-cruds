<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.3
 * @name HelperLegend
 * @file HelperLegend.php
 * @date 30-03-2013 10:52 (SPAIN)
 * @observations:
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperLegend extends TheFrameworkHelper
{
    public function __construct($innerhtml = "", $id = "", $class = "", $style = "", $extras = array())
    {
        $this->_type = "legend";
        $this->_idprefix = "";
        $this->_id = $id;

        $this->_inner_html = $innerhtml;
        if($class) {
            $this->classes[] = $class;
        }
        if($style) {
            $this->styles[] = $style;
        }
        $this->extras = $extras;
        $this->_style = $style;
    }

    //legend
    public function getHtml()
    {
        $htmlToReturn = "";
        if($this->_comments) {
            $htmlToReturn = "<!-- $this->_comments -->\n";
        }
        $htmlToReturn .= $this->getOpentag();
        //Agrega a inner_html los valores obtenidos con get_html de cada objeto en $this->arInnerObjects
        $this->loadInnerObjects();
        $htmlToReturn .= $this->_inner_html;
        $htmlToReturn .= $this->getClosetag();
        return $htmlToReturn;
    }

    public function getOpentag()
    {
        //Ejem: <fieldset> <legend>Personalia:</legend> Name: <input type="text" size="30"><br>
        $htmlOpenTag = "<$this->_type";
        if($this->_id) {
            $htmlOpenTag .= " id=\"$this->_idprefix$this->_id\"";
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
        //atributos extra
        if($this->_attr_dbfield) {
            $htmlOpenTag .= " dbfield=\"$this->_attr_dbfield\"";
        }
        if($this->_attr_dbtype) {
            $htmlOpenTag .= " dbtype=\"$this->_attr_dbtype\"";
        }
        if($this->extras) {
            $htmlOpenTag .= " ".$this->getExtras();
        }
        //if($this->_isPrimaryKey) $sHtmlOpenTag .= " pk=\"pk\"";
        //if($this->_attr_dbtype) $sHtmlOpenTag .= " dbtype=\"$this->_attr_dbtype\"";
        $htmlOpenTag .= ">";
        return $htmlOpenTag;
    }

    //**********************************
    //             TO HIDE
    //**********************************
    //private function get_closetag(){;}
    //private function get_opentag(){;}

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
}//HelperLegend
