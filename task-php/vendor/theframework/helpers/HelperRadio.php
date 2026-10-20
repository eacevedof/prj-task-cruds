<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.2
 * @name HelperRadio
 * @date 15-07-2013 19:51
 * @file HelperRadio.php
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperRadio extends TheFrameworkHelper
{
    private $_arOptions;
    private $_value_to_check;

    //private $_name;
    private $_legendtext;
    //private $_inFieldsetDiv=true;

    public function __construct($options, $grpname, $legendtext = "", $valuetocheck = "", $class = "", $extras = array())
    {
        //$this->_id = ""; el id se aplica por check no por legend
        $this->_type = "radio";
        $this->_idprefix = "";
        $this->_arOptions = $options;
        $this->_value_to_check = $valuetocheck;

        $this->_name = $grpname;
        $this->_legendtext = $legendtext;
        if($class) {
            $this->classes[] = $class;
        }
        $this->extras = $extras;
    }

    public function getHtml()
    {
        $htmlToReturn = "";
        //$sHtmlFieldSet = "<fieldset>\n";
        //$sHtmlFieldSetEnd = "</fieldset>\n";
        $htmlToReturn = "";
        if($this->_comments) {
            $htmlToReturn .= "<!-- $this->_comments -->\n";
        }
        if($this->_legendtext) {
            $htmlToReturn .= "<legend>$this->_legendtext</legend>\n";
        }

        $i = 0;
        foreach($this->_arOptions as $value => $label) {
            $isChecked = ($this->_value_to_check == $value);
            $id = $this->_idprefix.$this->_name."_".$i;
            $id = str_replace("[]", "", $id);
            $labelObject = new HelperLabel($id, $label, "lbl$id");
            $htmlToReturn .= $this->buildInputRadio($id, $value, $labelObject, $isChecked);
            $i++;
        }
        //if($this->_inFieldsetDiv) $sHtmlToReturn = $sHtmlFieldSet.$sHtmlToReturn.$sHtmlFieldSetEnd;
        return $htmlToReturn;
    }

    private function buildInputRadio($id, $value, ?HelperLabel $label = null, $isChecked = false)
    {
        $this->_id = $id;

        $htmlToReturn = "";
        $htmlToReturn .= "<input";
        if($this->_type) {
            $htmlToReturn .= " type=\"$this->_type\"";
        }
        if($this->_id) {
            $htmlToReturn .= " id=\"$id\"";
        }
        if($this->_name) {
            $htmlToReturn .= " name=\"$this->_idprefix$this->_name\"";
        }
        if($value) {
            $htmlToReturn .= " value=\"$value\"";
        }
        if($isChecked) {
            $htmlToReturn .= " checked" ;
        }
        //propiedades html5
        //if($this->_maxlength)$sHtmlToReturn .= " maxlength=\"$this->_maxlength\"";
        if($this->_isDisabled) {
            $htmlToReturn .= " disabled";
        }
        if($this->_isReadOnly) {
            $htmlToReturn .= " readonly";
        }
        //if($this->_isRequired) $sHtmlToReturn .= " required";
        //eventos
        if($this->_js_onblur) {
            $htmlToReturn .= " onblur=\"$this->_js_onblur\"";
        }

        if($this->_js_onchange && $this->_isPostback) {
            $htmlToReturn .= " onchange=\"$this->_js_onchange;postback(this);\"";
        } elseif($this->_js_onchange) {
            $htmlToReturn .= " onchange=\"$this->_js_onchange\"";
        }
        //postback(): Funcion definida en HelperJavascript
        elseif($this->_isPostback) {
            $htmlToReturn .= " onchange=\"postback(this);\"";
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
        $htmlToReturn .= " />\n";
        if($label) {
            $htmlToReturn .= $label->getHtml();
        }

        return $htmlToReturn;
    }

    //**********************************
    //             SETS
    //**********************************
    public function setName($value)
    {
        $this->_name = $value;
    }
    public function setValueToCheck($value)
    {
        $this->_value_to_check = $value;
    }
    public function setLegendtext($value)
    {
        $this->_legendtext = $value;
    }

    //**********************************
    //             GETS
    //**********************************
    public function getName()
    {
        return $this->_name;
    }
    public function getValueChecked()
    {
        return $this->_value_to_check;
    }
    public function getLegendtext()
    {
        return $this->_legendtext;
    }
}
/*<form>
  <fieldset>
    <legend>Personalia:</legend> The <legend> tag defines a caption for the <fieldset> element.
    Name: <input type="text" size="30"><br>
    Email: <input type="text" size="30"><br>
    Date of birth: <input type="text" size="10">
  </fieldset>
</form>
 *
<form ...>
    <input type="radio" name="creditcard" value="Visa" id="visa" />
    <label for="visa">Visa</label>
    <input type="radio" name="creditcard" value="Mastercard" id="mastercard" />
    <label for="mastercard">Mastercard</label>
</form>
 */
