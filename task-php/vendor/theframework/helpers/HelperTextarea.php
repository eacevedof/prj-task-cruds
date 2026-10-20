<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.2.2
 * @name HelperTextarea
 * @date 21-11-2016 22:24 (SPAIN)
 * @file HelperTextarea.php
 * @observations
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperTextarea extends TheFrameworkHelper
{
    private $_cols;
    private $_rows;
    private $isCounterSpan;
    private $isCounterJs;

    public function __construct($id = "", $name = "", $innerhtml = "", $extras = "", $maxlength = -1, $cols = 40, $rows = 8, $class = "", $style = "", ?HelperLabel $label = null)
    {
        $this->_type = "textarea";

        $this->_idprefix = "";
        $this->_id = $id;
        $this->_inner_html = $innerhtml;
        $this->_name = $name;
        $this->_cols = $cols;
        $this->_rows = $rows;

        if($class) {
            $this->classes[] = $class;
        }
        if($style) {
            $this->styles[] = $style;
        }

        $this->_maxlength = $maxlength;
        $this->extras = $extras;
        $this->label = $label;

        $this->isCounterSpan = true;
        $this->isCounterJs = true;
    }//__construct

    private function jsCounter()
    {
        ?>

<script type="text/javascript" helper="textarea.js_counter">
    var fn_txaspan = function(oTextarea,sValue)
    {
        var sNameSpan = "sp"+oTextarea.id;
        var oSpan = document.getElementById(sNameSpan);
        if(oSpan)
            oSpan.innerHTML = sValue;
    };
    
    var fn_txamaxlength = function(oTextarea,oEvent)
    {
        var sInnerHtml = "";
        var isEvent = true;
        if(oTextarea)
        {
            var iMaxLen = oTextarea.getAttribute("maxlength") || 1000;
            sInnerHtml = oTextarea.value;
            var iLen = sInnerHtml.length;
            if(iLen>iMaxLen)
            {
                isEvent = false;
                iLen = iMaxLen;
                oTextarea.value = sInnerHtml;
            }
            var sLenText = iLen+"/"+iMaxLen;
            fn_txaspan(oTextarea,sLenText);
        }
        return isEvent;
    };
</script>    
<?php
    }//js_counter

    public function getHtml()
    {
        $htmlToReturn = "";
        if($this->label) {
            $htmlToReturn .= $this->label->getHtml();
        }
        //Una longitud de 0 tiene un comportamiento parecido a un bloqueado
        if($this->_maxlength > -1) {
            $this->_js_onkeyup .= " return fn_txamaxlength(this,event);";
        }

        if($this->_comments) {
            $htmlToReturn .= "<!-- $this->_comments -->\n";
        }
        $htmlToReturn .= $this->getOpentag();
        $htmlToReturn .= htmlentities($this->_inner_html);
        $htmlToReturn .= $this->getClosetag();

        if($this->isCounterSpan) {
            $htmlToReturn .= "\n<span id=\"sp$this->_idprefix$this->_id\"></span>";
        }

        if($this->isCounterJs) {
            $this->jsCounter();
        }

        return $htmlToReturn;
    }//get_html

    public function getOpentag()
    {
        $htmlOpenTag = "<$this->_type ";
        if($this->_id) {
            $htmlOpenTag .= "id=\"$this->_idprefix$this->_id\" ";
        }
        if($this->_name) {
            $htmlOpenTag .= "name=\"$this->_idprefix$this->_name\" ";
        }
        if($this->_rows) {
            $htmlOpenTag .= "rows=\"$this->_rows\" ";
        }
        if($this->_cols) {
            $htmlOpenTag .= "cols=\"$this->_cols\" ";
        }
        //propiedades html5
        if($this->_isDisabled) {
            $htmlOpenTag .= "disabled ";
        }
        if($this->_isReadOnly) {
            $htmlOpenTag .= "readonly ";
        }
        if($this->_isRequired) {
            $htmlOpenTag .= "required ";
        }
        //eventos
        if($this->_js_onfocus) {
            $htmlOpenTag .= "onfocus=\"$this->_js_onfocus\" ";
        }
        if($this->_js_onblur) {
            $htmlOpenTag .= "onblur=\"$this->_js_onblur\" ";
        }
        if($this->_js_onchange) {
            $htmlOpenTag .= "onchange=\"$this->_js_onchange\" ";
        }
        if($this->_js_onclick) {
            $htmlOpenTag .= "onclick=\"$this->_js_onclick\" ";
        }

        if($this->_js_onkeypress) {
            if($this->_isEnterInsert) {
                $htmlOpenTag .= "onkeypress=\"$this->_js_onkeypress;onenter_insert(event);\" ";
            } elseif($this->_isEnterUpdate) {
                $htmlOpenTag .= "onkeypress=\"$this->_js_onkeypress;onenter_update(event);\" ";
            } elseif($this->_isEnterSubmit) {
                $htmlOpenTag .= "onkeypress=\"$this->_js_onkeypress;onenter_submit(event);\" ";
            }
            $htmlOpenTag .= "onkeypress=\"$this->_js_onkeypress\" ";
        }

        if($this->_js_onkeydown) {
            $htmlOpenTag .= "onkeydown=\"$this->_js_onkeydown\" ";
        }
        if($this->_js_onkeyup) {
            $htmlOpenTag .= "onkeyup=\"$this->_js_onkeyup\" ";
        }
        //postback(): Funcion definida en HelperJavascript
        elseif($this->_isEnterInsert) {
            $htmlOpenTag .= "onkeypress=\"onenter_insert(event);\" ";
        } elseif($this->_isEnterUpdate) {
            $htmlOpenTag .= "onkeypress=\"onenter_update(event);\" ";
        } elseif($this->_isEnterSubmit) {
            $htmlOpenTag .= "onkeypress=\"onenter_submit(event);\" ";
        }

        if($this->_js_onmouseover) {
            $htmlOpenTag .= "onmouseover=\"$this->_js_onmouseover\" ";
        }
        if($this->_js_onmouseout) {
            $htmlOpenTag .= "onmouseout=\"$this->_js_onmouseout\" ";
        }

        //aspecto
        $this->loadCssclass();
        if($this->_class) {
            $htmlOpenTag .= "class=\"$this->_class\" ";
        }
        $this->loadStyle();
        if($this->_style) {
            $htmlOpenTag .= "style=\"$this->_style\" ";
        }
        //atributos extras
        if($this->_maxlength) {
            $htmlOpenTag .= "maxlength=\"$this->_maxlength\" ";
        }
        if($this->extras) {
            $htmlOpenTag .= " ".$this->getExtras();
        }
        if($this->_isPrimaryKey) {
            $htmlOpenTag .= "pk=\"pk\" ";
        }
        if($this->_attr_dbtype) {
            $htmlOpenTag .= "dbtype=\"$this->_attr_dbtype\" ";
        }

        $htmlOpenTag .= ">\n";
        return $htmlOpenTag;
    }//get_opentag

    //**********************************
    //             SETS
    //**********************************
    public function setMaxlength($value)
    {
        $this->_maxlength = $value;
    }
    public function setRows($value)
    {
        $this->_rows = $value;
    }
    public function setCols($value)
    {
        $this->_cols = $value;
    }
    public function setCounterspan($isOn = true)
    {
        $this->isCounterSpan = $isOn;
    }
    public function setCounterjs($isOn = true)
    {
        $this->isCounterJs = $isOn;
    }

    //**********************************
    //             GETS
    //**********************************
    public function getMaxlength()
    {
        return $this->_maxlength;
    }
    public function readonly($isReadOnly = true)
    {
        $this->_isReadOnly = $isReadOnly;
    }

}//HelperTextarea
