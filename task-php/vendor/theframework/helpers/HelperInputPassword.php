<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.3
 * @name HelperInputPassword
 * @date 22-11-2016 09:00
 * @file HelperInputPassword.php
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperInputPassword extends TheFrameworkHelper
{
    public function __construct($id = "", $name = "", $value = "", $length = 50, $class = "", ?HelperLabel $label = null)
    {
        $this->label = $label;
        $this->_idprefix = "";
        $this->_type = "password";
        $this->_id = $id;
        $this->_name = $name;
        $this->_value = $value;
        $this->_maxlength = $length;
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
            $htmlToReturn .= " value=\"{$this->getCleaned($this->_value)}\"";
        }
        //propiedades html5
        if($this->_maxlength) {
            $htmlToReturn .= " maxlength=\"$this->_maxlength\"";
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
    public function setValue($value, $asEntity = 0)
    {
        ($asEntity) ? $this->_value = htmlentities($value) : $this->_value = $value;
    }
    public function setMaxlength($numChars)
    {
        $this->_maxlength = $numChars;
    }
    public function readonly($isReadOnly = true)
    {
        parent::readonly($isReadOnly);
    }
    public function disabled($isDisabled = true)
    {
        parent::disabled($isDisabled);
    }
    public function required($isRequired = true)
    {
        $this->_isRequired = $isRequired;
    }

    //**********************************
    //             GETS
    //**********************************
    public function getName()
    {
        return $this->_name;
    }
    public function getValue($asEntity = 0)
    {
        if($asEntity) {
            return htmlentities($this->_value);
        } else {
            return $this->_value;
        }
    }
    public function getMaxlength()
    {
        return $this->_maxlength;
    }
    public function isReadonly()
    {
        return $this->_isReadOnly;
    }
}
