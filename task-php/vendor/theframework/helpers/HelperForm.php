<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.4.0
 * @name HelperForm
 * @file HelperForm.php
 * @date 30-04-2016 07:22 (SPAIN)
 * @observations: core library
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperForm extends TheFrameworkHelper
{
    private $_method;
    private $_enctype;
    private $_action;
    private $_js_onsubmit;

    private $fieldset;
    private $legend;

    public function __construct($id = "", $name = "", $method = "post", $innerhtml = "", $action = "", $class = "", $style = "", $extras = array(), $enctype = "", $onsubmit = "")
    {
        //enctype="multipart/form-data"
        $this->_type = "form";
        $this->_idprefix = "";
        $this->_id = $id;
        $this->_name = $name;
        $this->_inner_html = $innerhtml;
        if($class) {
            $this->classes[] = $class;
        }
        if($style) {
            $this->styles[] = $style;
        }

        $this->extras = $extras;
        $this->_method = $method;
        $this->_action = $action;
        $this->_enctype = $enctype;
        $this->_js_onsubmit = $onsubmit;
    }

    public function getHtml()
    {
        $htmlToReturn = "";
        if($this->_comments) {
            $htmlToReturn .= "<!-- $this->_comments -->\n";
        }
        $htmlToReturn .= $this->getOpentag();
        if($this->fieldset) {
            $htmlToReturn .= $this->fieldset->getOpentag();
        }
        if($this->legend) {
            $htmlToReturn .= $this->legend->getOpentag();
        }
        //Agrega a inner_html los valores obtenidos con get_html de cada objeto
        $this->loadInnerObjects();
        if($this->_inner_html) {
            $htmlToReturn .= "$this->_inner_html\n";
        }
        if($this->legend) {
            $htmlToReturn .= $this->legend->getClosetag();
        }
        if($this->fieldset) {
            $htmlToReturn .= $this->fieldset->getClosetag();
        }
        $htmlToReturn .= $this->getClosetag();

        return $htmlToReturn;
    }

    protected function loadInnerObjects()
    {
        foreach($this->innerObjects as $object) {
            //este objeto suele ser el wrapper
            if(method_exists($object, "getHtml")) {
                if($this->_isReadOnly) {
                    if(method_exists($object, "readonly")) {

                        $object->readonly();
                        $object->addClass("readonly");
                    }
                }
                $this->_inner_html .= $object->getHtml();
            } elseif(is_string($value)) {
                $this->_inner_html .= $value;
            }
        }
    }//load_inner_objects

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

        //if($this->_js_onkeypress) $sHtmlOpenTag .= " onkeypress=\"$this->_js_onkeypress\"";
        if($this->_js_onkeypress && $this->_isEnterInsert) {
            $htmlOpenTag .= " onkeypress=\"$this->_js_onkeypress;onenter_insert(event);\"";
        } elseif($this->_js_onkeypress && $this->_isEnterUpdate) {
            $htmlOpenTag .= " onkeypress=\"$this->_js_onkeypress;onenter_update(event);\"";
        } elseif($this->_js_onkeypress && $this->_isEnterSubmit) {
            $htmlOpenTag .= " onkeypress=\"$this->_js_onkeypress;onenter_submit(event);\"";
        } elseif($this->_js_onkeypress) {
            $htmlOpenTag .= " onkeypress=\"$this->_js_onkeypress\"";
        }
        //postback(): Funcion definida en HelperJavascript
        elseif($this->_isEnterInsert) {
            $htmlOpenTag .= " onkeypress=\"onenter_insert(event);\"";
        } elseif($this->_isEnterUpdate) {
            $htmlOpenTag .= " onkeypress=\"onenter_update(event);\"";
        } elseif($this->_isEnterSubmit) {
            $htmlOpenTag .= " onkeypress=\"onenter_submit(event);\"";
        }

        if($this->_js_onclick) {
            $htmlOpenTag .= " onclick=\"$this->_js_onclick\"";
        }
        if($this->_js_onfocus) {
            $htmlOpenTag .= " onfocus=\"$this->_js_onfocus\"";
        }
        if($this->_js_onsubmit) {
            $htmlOpenTag .= " onsubmit=\"$this->_js_onsubmit\"";
        }
        if($this->_js_onmouseover) {
            $htmlOpenTag .= " onmouseover=\"$this->_js_onmouseover\"";
        }
        if($this->_js_onmouseout) {
            $htmlOpenTag .= " onmouseout=\"$this->_js_onmouseout\"";
        }
        //propios del formulario
        if($this->_method) {
            $htmlOpenTag .= " method=\"$this->_method\"";
        }
        if($this->_action) {
            $htmlOpenTag .= " action=\"$this->_action\"";
        }
        if($this->_enctype) {
            $htmlOpenTag .= " enctype=\"$this->_enctype\"";
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

        $htmlOpenTag .= ">\n";
        return $htmlOpenTag;
    }

    //**********************************
    //             SETS
    //**********************************
    public function setLegend(HelperLegend $legend)
    {
        $this->legend = $legend;
    }
    public function setFieldset(HelperFieldset $fieldset)
    {
        $this->fieldset = $fieldset;
    }
    public function setMethod($value)
    {
        $this->_method = $value;
    }
    public function setAction($value)
    {
        $this->_action = $value;
    }
    public function setEnctype($value)
    {
        $this->_enctype = $value;
    }
    public function setJsOnsubmit($value)
    {
        $this->_js_onsubmit = $value;
    }
    public function addControltop($helper)
    {
        if($helper) {
            array_unshift($this->innerObjects, $helper);
        }
    }
    public function addControl($helper)
    {
        $this->innerObjects[] = $helper;
    }
    public function addControls($objControls)
    {
        $this->innerObjects = $objControls;
    }
    public function readonly($isReadOnly = true)
    {
        $this->_isReadOnly = $isReadOnly;
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

}//HelperForm
