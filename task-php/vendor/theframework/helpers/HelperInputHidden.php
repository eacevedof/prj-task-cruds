<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.6
 * @name Helper Hidden
 * @date 21-11-2016 08:58 (SPAIN)
 * @file HelperInputHidden.php
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperInputHidden extends TheFrameworkHelper
{
    //private $_name = "hid_name";

    public function __construct($id = "", $name = "", $value = "", $extras = array())
    {
        $this->_type = "hidden";
        $this->_idprefix = "";
        $this->_id = $id;
        $this->_value = $value;
        $this->_name = $name;
        $this->extras = $extras;
    }

    public function getHtml()
    {
        $htmlToReturn = "";
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

    //**********************************
    //             TO HIDE
    //**********************************
    //private function get_closetag(){;}
    //private function get_opentag(){;}

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
}//HelperInputHidden
