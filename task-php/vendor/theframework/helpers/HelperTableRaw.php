<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 2.0.0
 * @name HelperTableRaw
 * @date 14-05-2017 07:19 (SPAIN)
 * @file HelperTableRaw.php
 * @requires
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperTableRaw extends TheFrameworkHelper
{
    protected $labels;
    protected $rows;

    public function __construct($rows, $labels = array())
    {
        $this->_idprefix = "";
        $this->_type = "table";
        $this->rows = $rows;
        $this->labels = $labels;
    }

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
        if($this->_js_onkeypress) {
            $htmlOpenTag .= " onkeypress=\"$this->_js_onkeypress\"";
        }
        if($this->_js_onclick) {
            $htmlOpenTag .= " onclick=\"$this->_js_onclick\"";
        }
        if($this->_js_onfocus) {
            $htmlOpenTag .= " onfocus=\"$this->_js_onfocus\"";
        }
        if($this->_js_onmouseover) {
            $htmlOpenTag .= " onmouseover=\"$this->_js_onmouseover\"";
        }
        if($this->_js_onmouseout) {
            $htmlOpenTag .= " onmouseout=\"$this->_js_onmouseout\"";
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
        if($this->extras) {
            $htmlOpenTag .= " ".$this->getExtras();
        }

        $htmlOpenTag .= ">\n";
        return $htmlOpenTag;
    }//get_opentag

    public function getHtml()
    {
        $htmlToReturn = $this->getOpentag()."\n";
        //si no se hay pasado etiquetas se intentan recuperar desde la primera fila
        if(!$this->labels) {
            if(is_array($this->rows[0])) {
                $this->labels = array_keys($this->rows[0]);
            }
        }

        //LABELS EN COLUMNAS
        if($this->labels) {
            $htmlToReturn .= "<tr>";
            foreach($this->labels as $label) {
                $htmlToReturn .= "<th>$label</th>";
            }
            $htmlToReturn .= "</tr>\n";
        }

        //DATOS
        if($this->rows) {
            foreach($this->rows as $rows) {
                $htmlToReturn .= "<tr>";
                foreach($rows as $value) {
                    $htmlToReturn .= "<td>$value</td>";
                }
                $htmlToReturn .= "</tr>\n";
            }
        }

        $htmlToReturn .= "</table>";
        return $htmlToReturn;
    }//get_html

    public function setData($rows)
    {
        $this->rows = $rows;
    }
    public function setLabels($labels)
    {
        $this->labels = $labels;
    }
}//HelperTableRaw
