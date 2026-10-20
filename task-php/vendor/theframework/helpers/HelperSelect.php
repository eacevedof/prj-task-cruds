<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.1.0
 * @name HelperSelect
 * @date 17-02-2016 17:27
 * @file HelperSelect.php
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperSelect extends TheFrameworkHelper
{
    private $options;
    private $valuesToSelect = null;
    private $_selected_as_hidden = null;
    private $_isMultiple;
    private $_size;

    public function __construct($options, $id = "", $name = "", ?HelperLabel $label = null, $valueToSelect = "", $size = 1, $isMultiple = false, $extras = "", $class = "", $isReadOnly = false)
    {
        $this->_type = "select";
        $this->valuesToSelect = $valueToSelect;

        $this->options = $options;
        $this->_idprefix = "";
        $this->_id = $id;
        $this->_name = $name;
        $this->_isMultiple = $isMultiple;
        if($this->_size > 1) {
            $this->_isMultiple = true;
        }
        $this->_size = $size;
        $this->label = $label;
        $this->extras = $extras;
        if($class) {
            $this->classes[] = $class;
        }
        $this->_isReadOnly = $isReadOnly;
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
        $htmlToReturn .= $this->getOpentag();
        //INICIO OPTIONS
        if(!is_array($this->valuesToSelect)) {
            $valueToSelect = (string)$this->valuesToSelect;
        } else {
            $valueToSelect = $this->valuesToSelect;
        }

        //No es readonly
        if(!$this->_isReadOnly) {
            if(!$this->_isMultiple) {
                //bug($mxValueToSelect,"to sel of $this->_id");
                foreach($this->options as $value => $innerText) {
                    $optionValue = (string)$value;
                    //bug("$mxValueToSelect===$sOptionValue");
                    $isSelected = ($valueToSelect === $optionValue);
                    $htmlToReturn .= $this->buildHtmloption($value, $innerText, $isSelected);
                }
            }
            //Multiple
            else {
                foreach($this->options as $value => $innerText) {
                    if(is_array($valueToSelect)) {
                        $isSelected = in_array($value, $valueToSelect);
                    } else {
                        $isSelected = ($valueToSelect == ((string)$value));
                    }
                    $htmlToReturn .= $this->buildHtmloption($value, $innerText, $isSelected);
                }
            }
        }
        //es readonly
        else {
            if(!$this->_isMultiple) {
                //Hay dos opciones y una es vacia.
                if(count($this->options) <= 2 && key_exists("", $this->options)) {
                    unset($this->options[""]);
                    $itemReadonly = $this->options;
                }
                //no tiene item en blanco
                else {
                    //recupera el valor de autoselección
                    $itemReadonly = $this->getItemReadonly($this->options, $valueToSelect);
                }
                foreach($itemReadonly as $value => $text) {
                    $htmlToReturn .= $this->buildHtmloption($value, $text, true);
                }
            }
            //es readonly y multiple
            else {
                //bug("is multiple"); bug(is_array($this->_isMultiple),"is multiple");
                //Falta implementar
            }
        }//fin es readonly
        //FIN OPTIONS

        $htmlToReturn .= $this->getClosetag();
        $htmlToReturn .= $this->_selected_as_hidden;
        return $htmlToReturn;
    }

    public function getOpentag()
    {
        $htmlToReturn = "<$this->_type";
        if($this->_id) {
            $htmlToReturn .= " id=\"$this->_idprefix$this->_id\"";
        }
        //Nombre dependiendo si es multiple o no
        if($this->_isMultiple) {
            $htmlToReturn .= " name=\"$this->_idprefix$this->_name[]\"";
        } else {
            $htmlToReturn .= " name=\"$this->_idprefix$this->_name\"";
        }

        if($this->_size) {
            $htmlToReturn .= " size=\"$this->_size\"";
        }
        if($this->_isMultiple) {
            $htmlToReturn .= " multiple";
        }
        if($this->_isDisabled) {
            $htmlToReturn .= " disabled";
        }
        //if($this->_isReadOnly) $sHtmlToReturn .= " readonly"; no existe esta propiedad para select
        if($this->_isRequired) {
            $htmlToReturn .= " required";
        }
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
        $htmlToReturn .= ">\n";
        return $htmlToReturn;
    }

    /**
     * @param array $arOptions
     * @param string $sValueToSelect
     * @return array De un solo item tipo array[$value]=innertext
     */
    private function getItemReadonly($options, $valueToSelect)
    {
        $itemReadOnly = array("" => "");
        foreach($options as $optValue => $optText) {
            if($valueToSelect == (string)$optValue) {
                $itemReadOnly = array($optValue => $optText);
                return $itemReadOnly;
            }
        }
        return $itemReadOnly;
    }

    /**
     * @param array $arOptions
     * @param array $arValuesToSelect
     * @return array
     */
    private function getItemsReadonly($options, $valuesToSelect = array())
    {
        $itemReadOnly = array();

        foreach($options as $optValue => $optText) {
            foreach($valuesToSelect as $value) {
                if($value == (string)$optValue) {
                    $itemReadOnly[$optValue] = $optText;
                }
            }
        }

        if(empty($itemReadOnly)) {
            $itemReadOnly = array("" => "");
        }

        return $itemReadOnly;
    }

    private function buildHtmloption($value, $innertext, $isSelected = false)
    {
        $option = "";
        $option .= "\t<option";
        $option .= " value=\"$value\"";
        if($isSelected) {
            $option .= " selected";
        }
        $option .= ">";
        $option .= htmlentities($innertext);
        $option .= "</option>\n";
        return $option;
    }

    //**********************************
    //             SETS
    //**********************************
    //protected function set_value(){;}

    public function readonly($isReadOnly = true)
    {
        $this->_isReadOnly = $isReadOnly;
    }
    public function setName($value)
    {
        $this->_name = $value;
    }
    public function setValueToSelect($values)
    {
        $this->valuesToSelect = $values;
    }
    public function setNullOptionText($value)
    {
        $this->_null_option = $value;
    }
    public function setMultipleSize($value)
    {
        $this->_size = (int)$value;
        if($this->_size > 1) {
            $this->_isMultiple = true;
        }
    }

    /**
     * Usar en caso de aplicar el atributo disabled: set_extras("disabled");
     */
    public function setSelectedValueAsHiddenOn()
    {
        $this->_selected_as_hidden = "
        <input type=\"hidden\" name=\"$this->_name\" id=\"$this->_id\" value=\"$this->valuesToSelect\"/>\n";
    }

    public function setOptions($options)
    {
        $this->options = $options;
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
    //public function get_value(){return $this->_value;}
    public function getSelectedValue()
    {
        return $this->valuesToSelect;
    }
    public function getClosetag()
    {
        return parent::getClosetag();
    }
}
