<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.3.0
 * @name HelperCheckbox
 * @file HelperCheckbox.php
 * @date 03-05-2017 11:55 (SPAIN)
 * @observations:
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;
use TheFramework\Helpers\HelperLabel;
use TheFramework\Helpers\HelperLegend;
use TheFramework\Helpers\HelperFieldset;

class HelperCheckbox extends TheFrameworkHelper
{
    private $options;
    private $valuesToCheck;
    private $valuesDisabled;
    private $isGrouped;
    //private $sOutText;
    private $checksPerLine = 6;

    private $legend;
    private $fieldset;
    private $isLabeled;

    /**
     *
     * @param mixed $mxOptions array|string Las opciones deben ser array(value=>texto mostrar) si es string solo admite "value" ej. "val1|val2|..|valn"
     * @param string $name
     * @param mixed $mxValuesToCheck array|string
     * @param mixed $mxValuesDisabled  array|string
     * @param string $class
     * @param array $arExtras
     * @param type $isGrouped
     * @param HelperLegend $oLegend
     * @param HelperFieldset $oFieldset
     */
    public function __construct(
        $options = array(),
        $name = "",
        $valuesToCheck = array(),
        $valuesDisabled = array(),
        $class = "",
        $extras = "",
        $isGrouped = true,
        HelperLegend $legend = null,
        HelperFieldset $fieldset = null
    ) {
        $this->convStringToArray($options, 1);
        $this->convStringToArray($valuesToCheck);
        $this->convStringToArray($valuesDisabled);

        $this->_type = "checkbox";
        $this->_idprefix = "";
        $this->_name = $name;
        $this->_id = $name;
        $this->isLabeled = false;//permite customizar por defecto la etiqueta
        $this->options = $options;
        $this->valuesToCheck = $valuesToCheck;
        $this->valuesDisabled = $valuesDisabled;
        $this->isGrouped = $isGrouped;
        if($class) {
            $this->classes[] = $class;
        }
        $this->extras = $extras;
        $this->legend = $legend;
        $this->fieldset = $fieldset;
    }//__construct

    public function getHtml()
    {
        $htmlToReturn = "";
        if($this->_comments) {
            $htmlToReturn .= "<!-- $this->_comments -->\n";
        }

        if($this->fieldset) {
            $htmlToReturn .= $this->fieldset->getOpentag();
        }
        if($this->legend) {
            $htmlToReturn .= $this->legend->getHtml();
        }

        $option = 0;
        $numOptions = count($this->options);
        foreach($this->options as $value => $outText) {
            $isChecked = in_array($value, $this->valuesToCheck);
            $isReadOnly = in_array($value, $this->valuesDisabled);
            if($this->_id) {
                $checkId = "$this->_idprefix$this->_id";
            }
            if($numOptions > 1) {
                $checkId .= "_$option";
            }
            //bug($sCheckId);
            //calculo de checkboxes por linea. Si cumple se hace un salto
            if(($option % ($this->checksPerLine)) == 0 && $option > 0) {
                $htmlToReturn .= "<br/>";
            }
            $htmlToReturn .= $this->buildCheck($checkId, $value, $outText, $isChecked, $isReadOnly);
            //bug($sHtmlToReturn); die;
            $option++;
        }//foreach($this->arOptions)

        if($this->fieldset) {
            $htmlToReturn .= $this->fieldset->getClosetag();
        }
        //if($this->oLegend) $sHtmlToReturn .= $this->oLegend->get_closetag();
        return $htmlToReturn;
    }//get_html

    private function buildCheck($id, $value, $outText, $isChecked = false, $isReadOnly = false)
    {
        //$this->sOutText = $sOutText;
        $htmlCheckbox = "";
        //if($this->isLabeled) $sHtmlCheckbox .= "<div>";
        $htmlCheckbox .= "<input";
        $htmlCheckbox .= " type=\"$this->_type\" ";

        if($id) {
            $htmlCheckbox .= " id=\"$id\"";
        }
        $htmlCheckbox .= " name=\"$this->_idprefix$this->_name";
        if($this->isGrouped) {
            $htmlCheckbox .= "[]";
        }
        $htmlCheckbox .= "\"";
        $htmlCheckbox .= " value=\"$value\"";

        //eventos
        if($this->_js_onblur) {
            $htmlCheckbox .= " onblur=\"$this->_js_onblur\"";
        }

        if($this->_js_onchange && $this->_isPostback) {
            $htmlCheckbox .= " onchange=\"$this->_js_onchange;postback(this);\"";
        } elseif($this->_js_onchange) {
            $htmlCheckbox .= " onchange=\"$this->_js_onchange\"";
        }
        //postback(): Funcion definida en HelperJavascript
        elseif($this->_isPostback) {
            $htmlCheckbox .= " onchange=\"postback(this);\"";
        }

        if($this->_js_onclick) {
            $htmlCheckbox .= " onclick=\"$this->_js_onclick\"";
        }
        //if($this->_js_onkeypress) $sHtmlCheckbox .= " onkeypress=\"$this->_js_onkeypress\"";
        if($this->_js_onkeypress && $this->_isEnterInsert) {
            $htmlCheckbox .= " onkeypress=\"$this->_js_onkeypress;onenter_insert(event);\"";
        } elseif($this->_js_onkeypress && $this->_isEnterUpdate) {
            $htmlCheckbox .= " onkeypress=\"$this->_js_onkeypress;onenter_update(event);\"";
        } elseif($this->_js_onkeypress && $this->_isEnterSubmit) {
            $htmlCheckbox .= " onkeypress=\"$this->_js_onkeypress;onenter_submit(event);\"";
        } elseif($this->_js_onkeypress) {
            $htmlCheckbox .= " onkeypress=\"$this->_js_onkeypress\"";
        }
        //postback(): Funcion definida en HelperJavascript
        elseif($this->_isEnterInsert) {
            $htmlCheckbox .= " onkeypress=\"onenter_insert(event);\"";
        } elseif($this->_isEnterUpdate) {
            $htmlCheckbox .= " onkeypress=\"onenter_update(event);\"";
        } elseif($this->_isEnterSubmit) {
            $htmlCheckbox .= " onkeypress=\"onenter_submit(event);\"";
        }

        if($this->_js_onfocus) {
            $htmlCheckbox .= " onfocus=\"$this->_js_onfocus\"";
        }
        if($this->_js_onmouseover) {
            $htmlCheckbox .= " onmouseover=\"$this->_js_onmouseover\"";
        }
        if($this->_js_onmouseout) {
            $htmlCheckbox .= " onmouseout=\"$this->_js_onmouseout\"";
        }

        //aspecto
        $this->loadCssclass();
        if($this->_class) {
            $htmlCheckbox .= " class=\"$this->_class\"";
        }
        $this->loadStyle();
        if($this->_style) {
            $htmlCheckbox .= " style=\"$this->_style\"";
        }
        //atributos extras
        if($this->_attr_dbfield) {
            $htmlCheckbox .= " dbfield=\"$this->_attr_dbfield\"";
        }
        if($this->_attr_dbtype) {
            $htmlCheckbox .= " dbtype=\"$this->_attr_dbtype\"";
        }
        if($this->extras) {
            $htmlCheckbox .= " ".$this->getExtras();
        }

        if($isChecked) {
            $htmlCheckbox .= " checked";
        }
        if($isReadOnly) {
            $htmlCheckbox .= " disabled";
        }
        $htmlCheckbox .= ">";
        //out text
        if($this->isLabeled) {
            $label = new HelperLabel($id, $outText);
            $htmlCheckbox .= $label->getHtml();
            //$sHtmlCheckbox .= "</div>";
        } elseif($outText) {
            $htmlCheckbox .= "$outText";
        }
        return $htmlCheckbox;
    }//build_check

    private function convStringToArray(&$strArray, $isValIndex = 0)
    {
        if(!$strArray) {
            $strArray = [];
        }

        if(is_string($strArray)) {
            $strArray = explode("|", $strArray);
            //val index se utiliza para las opciones ya que estas deben
            //ser index1->texto1,index2->texto2
            if($isValIndex) {
                $index = array();
                foreach($strArray as $v) {
                    $index[$v] = "";
                }
                $strArray = $index;
            }
        }//is string
    }//conv_string_to_array

    //**********************************
    //             SETS
    //**********************************
    public function setFieldset(HelperFieldset $fieldset)
    {
        $this->fieldset = $fieldset;
    }
    public function setLegend(HelperLegend $legend)
    {
        $this->legend = $legend;
    }
    //public function set_value($value){$this->conv_string_to_array($value);$this->arValuesToCheck = $value;}
    public function setValuesToCheck($values)
    {
        $this->convStringToArray($values);
        $this->valuesToCheck = $values;
    }
    public function notGroupedName($isOn = false)
    {
        $this->isGrouped = $isOn;
    }
    public function setChecksPerLine($numChecks)
    {
        $this->checksPerLine = $numChecks;
    }
    public function setOptions($options)
    {
        $this->convStringToArray($options, 1);
        $this->options = $options;
    }
    public function setUnlabeled($isOn = true)
    {
        $this->isLabeled = !$isOn;
    }
    public function setName($value)
    {
        $this->_name = $value;
    }

    //**********************************
    //             GETS
    //**********************************

    //**********************************
    //           MAKE PUBLIC
    //**********************************
    //public function show_opentag(){parent::show_opentag();}
    //public function show_closetag(){parent::show_closetag();}
}//HelperCheckbox
