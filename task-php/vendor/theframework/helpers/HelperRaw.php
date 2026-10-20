<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.1
 * @name HelperRaw
 * @date 06-12-2016 14:38
 * @file HelperRaw.php
 * @requires
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperRaw extends TheFrameworkHelper
{
    public function __construct($rawHtml = "")
    {
        $this->_inner_html = $rawHtml;
    }

    //Raw
    public function getHtml()
    {
        //Agrega a inner_html los valores obtenidos con get_html
        $htmlToReturn = "";
        $this->loadInnerObjects();
        $htmlToReturn .= $this->_inner_html;
        return $htmlToReturn;
    }

    //Escondo este metodo
    public function setRawhtml($rawHtml, $asEntity = 0)
    {
        parent::setInnerhtml($rawHtml, $asEntity);
    }

    //**********************************
    //             SETS
    //**********************************

    //**********************************
    //             GETS
    //**********************************

    //**********************************
    //           MAKE PUBLIC
    //**********************************
}
