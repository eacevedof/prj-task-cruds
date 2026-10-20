<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.3
 * @name HelperFieldset
 * @file HelperFieldset.php
 * @date 06-06-2013 14:02 (SPAIN)
 * @observations:
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperFieldset extends TheFrameworkHelper
{
    public function __construct($innerhtml = "", $id = "", $class = "", $style = "", $extras = array())
    {
        $this->_type = "fieldset";
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
    }

    //Fieldset
    public function getHtml()
    {
        $htmlToReturn = "";
        if($this->_comments) {
            $htmlToReturn = "<!-- $this->_comments -->\n";
        }
        $htmlToReturn .= $this->getOpentag();
        //Agrega a inner_html los valores obtenidos con get_html de cada objeto
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
    //             SETS
    //**********************************


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
