<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.1.2
 * @name HelperTableTr
 * @date 25-06-2014 09:25 ESP
 * @file HelperTableTr.php
 * @requires
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperTableTr extends TheFrameworkHelper
{
    protected $isRowHead = false;
    protected $isRowFoot = false;
    protected $colSpan = null;
    protected $rowSpan = null;
    protected $numCols = 0;

    protected $attrRownumber;

    public function __construct($innerObjectss = array(), $id = "", $class = "", $style = "", $colspan = "", $rowpan = "", $extras = array())
    {
        $this->_type = "tr";
        $this->_inner_html = "";
        $this->_idprefix = "tr";
        $this->_id = $id;

        //$this->_inner_html = $innertext;
        $this->innerObjects = $innerObjectss;
        $this->numCols = count($this->innerObjects);
        $this->colSpan = $colspan;
        $this->rowSpan = $rowpan;
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
        $htmlToReturn = $this->getOpentag();
        //$this->_inner_html .= $this->get_tds_as_string();
        $this->loadInnerObjects();
        if($this->_inner_html) {
            $htmlToReturn .= $this->_inner_html;
        }
        $htmlToReturn .= $this->getClosetag();
        return $htmlToReturn;
    }

    public function getOpentag()
    {
        //tr
        $htmlToReturn = "<$this->_type";
        if($this->_id) {
            $htmlToReturn .= " id=\"$this->_idprefix$this->_id\"";
        }
        if($this->rowSpan) {
            $htmlToReturn .= " rowspan=\"$this->rowSpan\"";
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
        //atributos extras
        if($this->extras) {
            $htmlToReturn .= " ".$this->getExtras();
        }
        if($this->_isPrimaryKey) {
            $htmlToReturn .= " pk=\"pk\"";
        }
        if($this->_attr_dbtype) {
            $htmlToReturn .= " dbtype=\"$this->_attr_dbtype\"";
        }
        if($this->attrRownumber !== "") {
            $htmlToReturn .= " rownumber=\"$this->attrRownumber\"";
        }
        $htmlToReturn .= ">\n";
        return $htmlToReturn;
    }

    public function getClosetag()
    {
        return parent::getClosetag();
    }

    //==================================
    //             SETS
    //==================================
    public function setColspan($value)
    {
        $this->colSpan = $value;
    }
    public function setObjtds($innerObjects = array())
    {
        $this->innerObjects = $innerObjects;
        $this->numCols = count($this->innerObjects);
    }
    public function setAsRowhead($isOn = true)
    {
        $this->isRowHead = $isOn;
    }
    public function setAsRowfoot($isOn = true)
    {
        $this->isRowFoot = $isOn;
    }
    public function setAttrRownumber($value)
    {
        $this->attrRownumber = $value;
    }
    public function addInnerObject($value)
    {
        $this->innerObjects[] = $value;
        $this->numCols = count($this->innerObjects);
    }
    public function addTd(HelperTableTd $td)
    {
        $this->innerObjects[] = $td;
        $this->numCols = count($this->innerObjects);
    }

    //==================================
    //             GETS
    //==================================
    public function getColspan()
    {
        return $this->colSpan;
    }
    public function getObjtds()
    {
        return $this->innerObjects;
    }
    public function isRowhead()
    {
        return $this->isRowHead;
    }
    public function isRowfoot()
    {
        return $this->isRowFoot;
    }
    public function getNumColumns()
    {
        return $this->numCols;
    }

}
