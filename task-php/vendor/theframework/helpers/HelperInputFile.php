<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.1
 * @name HelperInputFile
 * @date 20-04-2014 20:21 (SPAIN)
 * @file HelperInputFile.php
 * @observations
 * @requires:
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperInputFile extends TheFrameworkHelper
{
    protected $_maxsize;
    protected $_accept;
    //<input accept="audio/*|video/*|image/*|MIME_type">
    public function __construct($id = "", $name = "", $class = "", ?HelperLabel $label = null)
    {
        $this->label = $label;
        $this->_idprefix = "";
        $this->_type = "file";
        $this->_id = $id;
        $this->_name = $name;
        if($class) {
            $this->classes[] = $class;
        }
        $this->label = $label;
    }

    public function getHtml()
    {
        $htmlToReturn = "";

        if($this->label) {
            $htmlToReturn .= $this->label->getHtml();
        }
        if($this->_comments) {
            $htmlToReturn .= "<!-- $this->_comments -->\n";
        }
        $htmlToReturn .= "<input";
        if($this->_type) {
            $htmlToReturn .= " type=\"$this->_type\"";
        }
        if($this->_id) {
            $htmlToReturn .= " id=\"$this->_idprefix$this->_id\"";
        }
        if($this->_name) {
            $htmlToReturn .= " name=\"$this->_idprefix$this->_name\"";
        }
        if($this->_value || $this->_value == "0") {
            $htmlToReturn .= " value=\"$this->_value\"";
        }
        //bug($this->_value,"input_file $this->_id");
        //propiedades html5
        if($this->_accept) {
            $htmlToReturn .= " accept=\"$this->_accept\"";
        }
        if($this->_maxsize) {
            $htmlToReturn .= " maxsize=\"$this->_maxsize\"";
        }
        if($this->_isDisabled) {
            $htmlToReturn .= " disabled";
        }
        if($this->_isReadOnly) {
            $htmlToReturn .= " readonly";
        }
        if($this->_isRequired) {
            $htmlToReturn .= " required";
        }
        //bug($this->_isRequired,  $this->_id);
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

        if($this->_js_onkeypress && $this->_isEnterInsert) {
            $htmlToReturn .= " onkeypress=\"$this->_js_onkeypress;onenter_insert(event);\"";
        } elseif($this->_js_onkeypress && $this->_isEnterUpdate) {
            $htmlToReturn .= " onkeypress=\"$this->_js_onkeypress;onenter_update(event);\"";
        } elseif($this->_js_onkeypress && $this->_isEnterSubmit) {
            $htmlToReturn .= " onkeypress=\"$this->_js_onkeypress;onenter_submit(event);\"";
        } elseif($this->_js_onkeypress) {
            $htmlToReturn .= " onkeypress=\"$this->_js_onkeypress\"";
        }
        //postback(): Funcion definida en HelperJavascript
        elseif($this->_isEnterInsert) {
            $htmlToReturn .= " onkeypress=\"onenter_insert(event);\"";
        } elseif($this->_isEnterUpdate) {
            $htmlToReturn .= " onkeypress=\"onenter_update(event);\"";
        } elseif($this->_isEnterSubmit) {
            $htmlToReturn .= " onkeypress=\"onenter_submit(event);\"";
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
        if($this->_placeholder) {
            $htmlToReturn .= " placeholder=\"$this->_placeholder\"";
        }
        if($this->_attr_dbfield) {
            $htmlToReturn .= " dbfield=\"$this->_attr_dbfield\"";
        }
        if($this->_attr_dbtype) {
            $htmlToReturn .= " dbtype=\"$this->_attr_dbtype\"";
        }
        if($this->_isPrimaryKey) {
            $htmlToReturn .= " pk=\"pk\"";
        }
        if($this->extras) {
            $htmlToReturn .= " ".$this->getExtras();
        }

        $htmlToReturn .= ">\n";
        return $htmlToReturn;
    }

    //**********************************
    //             SETS
    //**********************************
    public function setName($value)
    {
        $this->_name = $value;
    }
    public function setValue($value, $void = null)
    {
        $this->_value = $value;
    }
    public function setMaxsize($numBytes)
    {
        $this->_maxsize = $numBytes;
    }
    public function readonly($isReadOnly = true)
    {
        $this->_isReadOnly = $isReadOnly;
    }
    public function disabled($isDisabled = true)
    {
        $this->_isDisabled = $isDisabled;
    }
    public function required($isRequired = true)
    {
        $this->_isRequired = $isRequired;
    }
    public function setAccept($accept)
    {
        $this->_accept = $accept;
    }
    //**********************************
    //             GETS
    //**********************************
    public function getName()
    {
        return $this->_name;
    }
    public function getMaxsize()
    {
        return $this->_maxsize;
    }
    public function isReadonly()
    {
        return $this->_isReadOnly;
    }
    public function getAccept()
    {
        return $this->_accept;
    }
}
