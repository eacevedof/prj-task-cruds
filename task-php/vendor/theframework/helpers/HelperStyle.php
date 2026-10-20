<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.0
 * @name Helper Style
 * @date 21-02-2013 09:45
 * @file HelperStyle.php
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperStyle extends TheFrameworkHelper
{
    private $_class_warning = ""; //yellow
    private $_class_error = ""; //red
    private $_class_success = ""; //green
    private $_class_tips = ""; //blue

    private $_class_default = "";
    private $_class_inverse = "";

    public function __construct()
    {
        $this->_type = "style";
    }

    //**********************************
    //             SETS
    //**********************************
    public function setClassWarning($value)
    {
        $this->_class_warning = $value;
    }
    public function setClassError($value)
    {
        $this->_class_error = $value;
    }
    public function setClassSuccess($value)
    {
        $this->_class_success = $value;
    }
    public function setClassTips($value)
    {
        $this->_class_tips = $value;
    }
    public function setClassDefault($value)
    {
        $this->_class_default = $value;
    }
    public function setClassInverse($value)
    {
        $this->_class_inverse = $value;
    }

    //**********************************
    //             GETS
    //**********************************
    public function getClassWarning()
    {
        return $this->_class_warning;
    }
    public function getClassError()
    {
        return $this->_class_error;
    }
    public function getClassSuccess()
    {
        return $this->_class_success;
    }
    public function getClassTips()
    {
        return $this->_class_tips;
    }
    public function getClassDefault()
    {
        return $this->_class_default;
    }
    public function getClassInverse()
    {
        return $this->_class_inverse;
    }
}
