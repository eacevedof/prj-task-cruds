<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.1.0
 * @name ComponentUri
 * @file ComponentUri.php
 * @date 21-10-2016 16:13 (SPAIN)
 * @observations:
 */
class ComponentUri //extends TheFrameworkComponent
{
    private $isPermaLink;
    private $isoLanguage;
    private $package;
    private $controller;
    private $partial;
    private $method;
    private $extra;

    /**
     *
     * @param type $sController
     * @param type $sPartial
     * @param type $sMethod
     * @param string|array $mxExtra
     * @param type $sPackage
     * @param type $isPermaLink
     */
    public function __construct($controller = null, $partial = null, $method = null, $extra = null, $package = null, $isPermaLink = 0)
    {
        if (isset($_GET["tfw_iso_language"])) {
            $this->isoLanguage = $_GET["tfw_iso_language"];
        }
        $this->isPermaLink = $isPermaLink;
        $this->package = $package;
        $this->controller = $controller;
        $this->partial = $partial;
        $this->method = $method;
        $this->loadExtra($extra);
    }

    private function loadExtra($extra)
    {
        $this->extra = array();
        if ($extra) {
            if (is_string($extra) && strstr($extra, ",")) {
                $keyVals = explode(",", $extra);
                if ($this->isPermaLink) {
                    foreach($keyVals as $keyVal) {
                        $val = explode("=", $keyVal);
                        $val = $val[1];
                        $this->extra[] = $val;
                    }
                } else {
                    foreach($keyVals as $keyVal) {
                        $this->extra[] = $keyVal;
                    }
                }
            } elseif (is_string($extra)) {
                $keyVals = explode("&", $extra);
                if ($this->isPermaLink) {
                    foreach($keyVals as $keyVal) {
                        $val = explode("=", $keyVal);
                        $val = $val[1];
                        $this->extra[] = $val;
                    }
                } else {
                    foreach($keyVals as $keyVal) {
                        $this->extra[] = $keyVal;
                    }
                }
            } elseif (is_array($extra)) {
                foreach($extra as $key => $value) {
                    $this->extra[] = "$key=$value";
                }
            }
        }
    }//load_extra

    private function getJoinedExtra()
    {
        $extra = "";
        if ($this->extra) {
            if ($this->isPermaLink) {
                $extra = implode("/", $this->extra);
            } else {
                $extra = implode("&", $this->extra);
            }
        }
        return $extra;
    }//get_joined_extra

    public function getBuilt()
    {
        $url = "";
        //recupero los parametros extra
        $extra = $this->getJoinedExtra();
        if ($this->isPermaLink) {
            $glue = "/";//separador web
            //si se está enrutando si el metodo (o la vista) es "get_list" se marca a nulo
            //para que no exista duplicidad puesto que get_list va intrinsico en /module/
            if ($this->isoLanguage) {
                $urls[] = $this->isoLanguage;
            }
            if ($this->package) {
                $urls[] = $this->package;
            }
            if ($this->controller) {
                $urls[] = $this->controller;
            }
            if ($this->partial) {
                $urls[] = $this->partial;
            }
            $method = $this->method;
            if ($method == "get_list") {
                $method = null;
            }
            if ($method) {
                $urls[] = $method;
            }
            $url .= $glue.implode($glue, $urls).$glue;
            if ($extra) {
                $url .= $extra.$glue;
            }
        }
        //& params url
        else {
            $url .= "index.php?";
            $glue = "&";
            if ($this->isoLanguage) {
                $urls[] = "lang=$this->isoLanguage";
            }
            if ($this->package) {
                $urls[] = "group=$this->package";
            }
            if ($this->controller) {
                $urls[] = "module=$this->controller";
            }
            if ($this->partial) {
                $urls[] = "section=$this->partial";
            }
            $method = $this->method;
            if (!$method) {
                $method = "get_list";
            }
            $urls[] = "view=$method";
            if ($extra) {
                $urls[] = $extra;
            }
            $url .= implode($glue, $urls);
        }
        return $url;
    }//get_built

    public function setIsoLanguage($value)
    {
        $this->isoLanguage = $value;
    }
    public function setPermalink($isOn = true)
    {
        $this->isPermaLink = $isOn;
    }
    public function setPackage($value)
    {
        $this->package = $value;
    }
    public function setController($value)
    {
        $this->controller = $value;
    }
    public function setPartial($value)
    {
        $this->partial = $value;
    }
    public function setMethod($value)
    {
        $this->method = $value;
    }
    public function setExtra($value)
    {
        $this->loadExtra($value);
    }
    public function addExtra($key, $value)
    {
        $this->extra[] = "$key=$value";
    }
    public function addExtra1($keyValue)
    {
        $this->extra[] = $keyValue;
    }
}//ComponentUri
