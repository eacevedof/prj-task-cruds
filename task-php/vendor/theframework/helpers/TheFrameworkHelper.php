<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.2.2
 * @name TheFrameworkHelper
 * @file TheFrameworkHelper.php
 * @date 30-07-2016 16:12 (SPAIN)
 *  load:22
 * @observations:
 */

namespace TheFramework\Helpers;

class TheFrameworkHelper
{
    protected $_comments = "";
    protected $_type = "";
    protected $_id = "";
    protected $_name = "";
    protected $_idprefix = "";
    protected $_maxlength = "";

    protected $_inner_html = "";
    protected $extras;
    protected $_placeholder;

    protected $_display = true;
    protected $_class = "";
    protected $_style = "";
    protected $classes = array();
    protected $styles = array();
    protected $innerObjects = array();
    protected $_value;

    //Esto emula el atributo bloqueado. Si esta a TRUE crea el control autoseleccionado con un
    //único valor en los objetos tipo select
    protected $_is_primarykey = false;
    protected $_isReadOnly = false;
    protected $_isRequired = false;
    protected $_isDisabled = false;
    protected $_isPrimaryKey = false;
    protected $_isPostback = false;
    protected $_isEnterInsert = false;//aplica action=insert
    protected $_isEnterUpdate = false;//aplica action=update
    protected $_isEnterSubmit = false;//no aplica nada

    protected $_js_onclick = null;
    protected $_js_onchange = null;
    protected $_js_onkeypress = null;
    protected $_js_onkeydown = null;
    protected $_js_onkeyup = null;

    protected $_js_onblur = null;
    protected $_js_onfocus = null;
    protected $_js_onmouseover = null;
    protected $_js_onmouseout = null;

    protected $_attr_dbtype = null;
    protected $_attr_dbfield = null;

    protected $bd = null;
    //HelperLabel
    protected $label = null;
    //HelperStyle
    protected $style = null;

    public function __construct()
    {
    }

    /**
     * Remplaza el atributo _class con las clases añadidas a arClasses
     */
    protected function loadCssclass()
    {
        if($this->classes) {
            $this->_class = trim(implode(" ", $this->classes));
        }
    }
    /**
     * Remplaza el atributo _style con los estilos en arStyles
    */
    protected function loadStyle()
    {
        if($this->styles) {
            $this->_style = trim(implode(";", $this->styles));
        }
    }
    /**
     * Agrega al atributo _inner_html el string obtenido con el metodo get_html()
     */
    protected function loadInnerObjects()
    {
        foreach($this->innerObjects as $value) {
            if(is_object($value) && method_exists($value, "getHtml")) {
                $this->_inner_html .= $value->getHtml();
            } elseif(is_string($value)) {
                $this->_inner_html .= $value;
            }
        }
        //            elseif(is_array($mxValue))
        //                die;
    }

    protected function concatParamValue($paramName, $value)
    {
        $value = urlencode($value);
        return "$paramName=$value";
    }

    protected function buildUriParamsWithKeys($keysAndValues = array())
    {
        $destinyKeysArray = array();
        $destinyKeys = "";
        foreach($keysAndValues as $fieldName => $value) {
            $destinyKeysArray[] = $this->concatParamValue($fieldName, $value);
        }

        if(!empty($destinyKeysArray)) {
            $destinyKeys = implode("&", $destinyKeysArray);
        }
        return $destinyKeys;
    }

    protected function extractFieldsAndValues($fields, $fieldNames)
    {
        $extracted = array();
        foreach($fields as $fieldName => $value) {
            if(in_array($fieldName, $fieldNames)) {
                $extracted[$fieldName] = $value;
            }
        }
        return $extracted;
    }

    /**
     * De un array tipo ("fieldname"=>"value") recupera solo los "value" de los "fieldname"
     * indicados en $arFieldNames
     * @param array $arFields
     * @param array $arFieldNames
     * @param boolean $asArray
     * @param string $sSeparator
     * @return mixed Array or String depende de $asArray
     */
    protected function extractValues($fields, $fieldNames, $asArray = false, $separator = "-")
    {
        $extractedArray = array();
        $extracted = "";
        foreach($fields as $fieldName => $value) {
            if(in_array($fieldName, $fieldNames)) {
                $extractedArray[] = $value;
            }
        }

        if(!empty($extractedArray) && !$asArray) {
            $extracted = implode($separator, $extractedArray);
        } elseif(empty($extractedArray) && !$asArray) {
            $extracted = "";
        } else {
            $extracted = $extractedArray;
        }
        return $extracted;
    }

    public function show()
    {
        if($this->_display) {
            echo $this->getHtml();
        }
    }

    //**********************************
    //             SETS
    //**********************************
    public function setComments($value)
    {
        $this->_comments = $value;
    }
    public function setIdprefix($value)
    {
        $this->_idprefix = $value;
    }
    public function setId($value)
    {
        $this->_id = $value;
    }
    public function setJsOnclick($value)
    {
        $this->_js_onclick = $value;
    }
    public function setJsOnchange($value)
    {
        $this->_js_onchange = $value;
    }
    public function setJsKeypress($value)
    {
        $this->_js_onkeypress = $value;
    }
    public function setJsKeydown($value)
    {
        $this->_js_onkeydown = $value;
    }
    public function setJsKeyup($value)
    {
        $this->_js_onkeyup = $value;
    }
    public function setJsOnblur($value)
    {
        $this->_js_onblur = $value;
    }
    public function setJsOnfocus($value)
    {
        $this->_js_onfocus = $value;
    }
    public function setJsOnmouseover($value)
    {
        $this->_js_onmouseover = $value;
    }
    public function setJsOnmouseout($value)
    {
        $this->_js_onmouseout = $value;
    }

    public function display($showIt = true)
    {
        $this->_display = $showIt;
    }
    protected function required($isRequired = true)
    {
        $this->_isRequired = $isRequired;
    }
    protected function readonly($isReadOnly = true)
    {
        $this->_isReadOnly = $isReadOnly;
    }
    protected function disabled($isDisabled = true)
    {
        $this->_isDisabled = $isDisabled;
    }
    public function addClass($class)
    {
        if($class) {
            $this->classes[] = $class;
        }
    }

    public function addStyle($style)
    {
        if($style) {
            $this->styles[] = $style;
        }
    }
    /**
     *
     * @param mixed $mxValue helper object or string
     */
    public function addInnerObject($value)
    {
        if($value) {
            $this->innerObjects[] = $value;
        }
    }

    public function setExtras(array $value)
    {
        $this->extras = array();
        if($value) {
            $this->extras = $value;
        }
    }
    public function addExtras($key, $value)
    {
        if($key) {
            $this->extras[$key] = $value;
        } else {
            $this->extras[] = $value;
        }
    }

    protected function setPlaceholder($value)
    {
        $this->_placeholder = htmlentities($value);
    }
    public function setAttrDbtype($value)
    {
        $this->_attr_dbtype = $value;
    }
    public function setAttrDbfield($value)
    {
        $this->_attr_dbfield = $value;
    }
    public function setAsPrimarykey($isPk = true)
    {
        $this->_is_primarykey = $isPk;
    }
    public function setInnerhtml($innerHtml, $asEntity = 0)
    {
        if($asEntity) {
            $this->_inner_html = htmlentities($innerHtml);
        } else {
            $this->_inner_html = $innerHtml;
        }
    }
    public function setType($value)
    {
        $this->_type = $value;
    }
    public function setPostback($isOn = true)
    {
        $this->_isPostback = $isOn;
    }
    public function onEnterinsert($isOn = true)
    {
        $this->_isEnterInsert = $isOn;
    }
    public function onEnterupdate($isOn = true)
    {
        $this->_isEnterUpdate = $isOn;
    }
    public function onEntersubmit($isOn = true)
    {
        $this->_isEnterSubmit = $isOn;
    }

    protected function setName($value)
    {
        $this->_name = $value;
    }

    protected function setLabel(HelperLabel $label)
    {
        $this->label = $label;
    }
    public function setClass($class)
    {
        $this->classes = array();
        if($class) {
            $this->classes[] = $class;
        }
    }
    public function setStyle($value)
    {
        $this->styles = array();
        if($value) {
            $this->styles[] = $value;
        }
    }
    protected function setStyleObject(HelperStyle $style)
    {
        $this->style = $style;
    }
    protected function resetClass()
    {
        $this->classes = array();
        $this->_class = "";
    }
    protected function resetStyle()
    {
        $this->styles = array();
        $this->_style = "";
    }
    protected function resetInnerObject()
    {
        $this->innerObjects = array();
    }
    protected function setInnerObjects($objHelpers)
    {
        $this->innerObjects = $objHelpers;
    }
    protected function setValue($value, $asEntity = 0)
    {
        ($asEntity) ? $this->_value = htmlentities($value) : $this->_value = $value;
    }
    protected function getCleaned($string)
    {
        $string = str_replace("\"", "&quot;", $string);
        return $string;
    }

    //**********************************
    //             GETS
    //**********************************
    public function getId()
    {
        return $this->_id;
    }
    public function getType()
    {
        return $this->_type;
    }
    public function getClass()
    {
        return $this->_class;
    }
    public function getExtras($asString = true)
    {
        $extras = array();
        if($asString) {
            foreach($this->extras as $key => $value) {
                //Esto no funcionaría si aplicase valores para mostrar un atributo 0="nuevo"
                if(is_integer($key)) {
                    if(strstr($value, "=")) {
                        $extras[] = $value;
                    } else {
                        $extras[] = "$key=\"$value\"";
                    }
                } else {
                    $extras[] = "$key=\"$value\"";
                }
            }
            return implode(" ", $extras);
        } else {
            return $this->extras;
        }
    }//get_extras

    public function getInnerhtml()
    {
        return $this->_inner_html;
    }
    protected function isDisabled()
    {
        return $this->_isDisabled;
    }

    public function getDbtype()
    {
        return $this->_attr_dbtype;
    }
    public function isPrimarykey()
    {
        return $this->_is_primarykey;
    }
    protected function getName()
    {
        return $this->_name;
    }

    protected function getIconPath($isIcon, $iconFile)
    {
        $iconPath = "images/";
        $iconPath .= $iconFile;
        if($isIcon && is_file($iconPath)) {
            return $iconPath;
        }
        return "";
    }

    //**********************************
    // OVERRIDE TO PUBLIC IF NECESSARY
    //**********************************
    //to override
    protected function getOpentag()
    {
    }
    //to override
    protected function getClosetag()
    {
        return "</$this->_type>\n";
    }
    //to override
    protected function showOpentag()
    {
        echo $this->getOpentag();
    }
    //to override
    protected function showClosetag()
    {
        echo $this->getClosetag();
    }
    protected function getLabel()
    {
        return $this->hlpLabel;
    }
    protected function getStyle()
    {
        return $this->hlpStyle;
    }
    protected function getPlaceholder()
    {
        return $this->_placeholder;
    }

    protected function isEnterinsert()
    {
        return $this->_isEnterInsert;
    }
    protected function isEnterupdate()
    {
        return $this->_isEnterUpdate;
    }
    protected function isEntersubmit()
    {
        return $this->_isEnterSubmit;
    }
    protected function getValue($asEntity = 0)
    {
        if($asEntity) {
            return htmlentities($this->_value);
        } else {
            return $this->_value;
        }
    }
}
