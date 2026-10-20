<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.13
 * @name HelperDate
 * @file HelperDate.php
 * @date 21-11-2016 09:08 (SPAIN)
 * @observations:
 * @requires:
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperDate extends TheFrameworkHelper
{
    private $_useClearButton = true;
    private $_convert_date_before_show = true;
    private $isIpadIphone;
    private $separator;

    public function __construct($id = "", $name = "", $value = "", $extras = array(), $maxlength = "", $class = "", ?HelperLabel $label = null)
    {
        //$this->_type = "date";
        $this->_type = "text";//12/11/2013 lo cambio a text pq el tipo date en dispositivos moviles no se comporta como se desea
        $this->_idprefix = "";//dtb
        $this->separator = "/";
        $this->_id = $id;
        $this->_value = $value;
        $this->_maxlength  = $maxlength;
        $this->_name = $name;
        if($class) {
            $this->classes[] = $class;
        }
        $this->extras = $extras;
        $this->label = $label;
    }

    private function toUserDate($anyDate)
    {
        $userDate = "";
        $anyDate = trim($anyDate);
        if($anyDate) {
            $anyDate = str_replace(" ", "", $anyDate);
            if(strstr($anyDate, "/")) {
                $sep = "/";
            } elseif(strstr($anyDate, "-")) {
                $sep = "-";
            }
            //si tiene formato hydra
            if(!$sep) {
                $sep = $this->separator;
                //bug($sHydraDate); die; 2001 10 25
                $year = substr($anyDate, 0, 4);
                $month = substr($anyDate, 4, 2);
                $day = substr($anyDate, 6, 2);
                $userDate = "$day$sep$month$sep$year";
            } else {
                $userDate = $anyDate;
            }
        }
        return $userDate;
    }

    public function getHtml()
    {
        //$this->isIpadIphone = 1;
        $htmlToReturn = "";
        if($this->_isReadOnly || $this->isIpadIphone) {
            $this->_type = "text";
        }
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
        if($this->_convert_date_before_show) {
            $this->_value = $this->toUserDate($this->_value);
        }
        if($this->_value) {
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

        //atributos extras 18/04/2014 este atributo lo dejo siempre para cualquier terminal
        if(!$this->_isReadOnly) {
            $htmlToReturn .= " as=\"date\"";
        }

        if($this->_placeholder) {
            $htmlToReturn .= " placeholder=\"$this->_placeholder\"";
        }
        if($this->_isPrimaryKey) {
            $htmlToReturn .= " pk=\"pk\"";
        }
        if($this->_attr_dbfield) {
            $htmlToReturn .= " dbfield=\"$this->_attr_dbfield\"";
        }
        if($this->_attr_dbtype) {
            $htmlToReturn .= " dbtype=\"$this->_attr_dbtype\"";
        }
        if($this->extras) {
            $htmlToReturn .= " ".$this->getExtras();
        }
        //if($this->_inFieldsetDiv) $sHtmlDate = $sHtmlFieldSet.$sHtmlDate.$sHtmlFieldSetEnd;

        //no funciona de este modo data-options=\"'useClearButton':true}\" con comillas simples encerrrando
        //a useClearButton
        if($this->_type == "date" && $this->_useClearButton) {
            $htmlToReturn .= " data-options='{\"useClearButton\":true}'";
        } elseif($this->_type == "date" && !$this->_useClearButton) {
            $htmlToReturn .= " data-options='{\"useClearButton\":false}'";
        }
        $htmlToReturn .= ">";
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
    public function setToday()
    {
        $this->_convert_date_before_show = false;
        $this->_value = date("d/m/Y");
    }
    public function inFieldsetdiv($isOn = true)
    {
        $this->_inFieldsetDiv = $isOn;
    }
    public function useClearbutton($isOn = true)
    {
        $this->_useClearButton = $isOn;
    }
    public function setIsIpadiphone($isOn = true)
    {
        $this->isIpadIphone = $isOn;
    }
    public function setSeparator($value)
    {
        $this->separator = $value;
    }
    public function required($isRequired = true)
    {
        $this->_isRequired = $isRequired;
    }
    public function readonly($isReadOnly = true)
    {
        $this->_isReadOnly = $isReadOnly;
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

}

/*
<fieldset data-role="controlgroup" data-type="horizontal">
<label for="det_DateP" class="clsRequired" >Fecha</label>
<input type="date" data-role="datebox" name="det_DateP" id="det_DateP"
value="<? echo (!$isNew) ? to_user_date($oActivity->get_datep()) : $oSite->get_today_date(); ?>"
required <?echo (!$isNew)? "readonly":""; ?> class="clsRequired" />
</fieldset>
*/
