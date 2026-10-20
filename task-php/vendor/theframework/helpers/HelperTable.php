<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.0.10
 * @name HelperTable
 * @date 30-07-2016 15:44 (SPAIN)
 * @file HelperTable.php
 * @requires
 *  helper_table_td.php
 *  ,helper_table_tr.php
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

import_helper("table_td,table_tr");
class HelperTable extends TheFrameworkHelper
{
    protected $objTrs = null;
    protected $useThead = false;
    protected $useTfoot = false;
    protected $numRows = 0;
    protected $numCols = 0;

    public function __construct($trs = array(), $id = "", $class = "", $style = "", $extras = array())
    {
        //clientbrowser,isMobileDevice,consolecalled,permalink
        parent::__construct();
        $this->_type = "table";
        $this->_idprefix = "tbl";
        $this->_id = $id;
        $this->_inner_html = "";

        $this->objTrs = $trs;
        $this->numRows = count($this->objTrs);
        $this->loadNumcols();

        if($class) {
            $this->classes[] = $class;
        }
        if($style) {
            $this->styles[] = $style;
        }
        $this->extras = $extras;
    }

    protected function loadNumcols()
    {
        $this->numCols = 0;
        if(isset($this->objTrs[0])) {
            if(is_object($this->objTrs[0])) {
                $this->numCols = count($this->objTrs[0]->getNumColumns());
            } elseif(is_array($this->objTrs[0])) {
                $this->numCols = count($this->objTrs[0]);
            } else {
                $this->numCols = substr_count($this->objTrs[0], "</td>");
            }
        }
    }

    //table
    public function getHtml()
    {
        $htmlToReturn = "";
        if($this->_comments) {
            $htmlToReturn .= "<!-- $this->_comments -->\n";
        }
        $htmlToReturn .= $this->getOpentag();
        //Agrega a inner_html los valores obtenidos con get_html de cada objeto en $this->arInnerObjects
        //No usa inner objects porque lo unico q se puede añadir a un elemento <table> como innerhtml son trs
        //$this->load_inner_objects();
        //$this->get_html_rows(): No es un simple bucle que recorre todos los objetos filas.
        //este metodo recupera las filas th, tfoot y tr y guarda en cada caso los indices correspondientes
        //para que despues se pinten de cabecera a pie. Esto da la versatilidad de añadir tr en cualquier puno del array
        //con su tipo y el metodo se encargará de ordenarlo
        if(!$this->_inner_html) {
            $this->_inner_html = $this->getHtmlRows();
        }
        $htmlToReturn .= $this->_inner_html;
        $htmlToReturn .= $this->getClosetag();
        return $htmlToReturn;
    }

    public function getOpentag()
    {
        $htmlToReturn = "<$this->_type";
        if($this->_id) {
            $htmlToReturn .= " id=\"$this->_idprefix$this->_id\"";
        }
        //eventos
        if($this->_js_onblur) {
            $htmlToReturn .= " onblur=\"$this->_js_onblur\"";
        }
        if($this->_js_onchange) {
            $htmlToReturn .= " onchange=\"$this->_js_onchange\"";
        }
        if($this->_js_onclick) {
            $htmlToReturn .= " onclick=\"$this->_js_onclick\"";
        }
        if($this->_js_onkeypress) {
            $htmlToReturn .= " onkeypress=\"$this->_js_onkeypress\"";
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
        //atributos extras
        if($this->extras) {
            $htmlToReturn .= " ".$this->getExtras();
        }
        //if($this->_isPrimaryKey) $sHtmlToReturn .= " pk=\"pk\"";
        //if($this->_attr_dbtype) $sHtmlToReturn .= " dbtype=\"$this->_attr_dbtype\"";
        $htmlToReturn .= ">\n";
        return $htmlToReturn;
    }

    protected function getHtmlRows()
    {
        $posHead = $this->getPositionsHead();
        $posFoot = $this->getPositionsFoot();
        $posBody = $this->getPositionsBody($posHead, $posFoot);

        $htmlRows = "";
        $htmlRows .= $this->buildThead($posHead);
        $htmlRows .= $this->buildTfoot($posFoot);
        $htmlRows .= $this->buildTbody($posBody);

        return $htmlRows;
    }

    /**
     * En el array de filas (arObjTrs) se puede añadir distintos tipos de datos.
     * Lo ideal es que sea un objeto ya que el framework se basa en POO.  No obstante
     * se puede añadir strings tipo raw o array de strings tds
     * @param mixed $mxTr Object HelperTr, array <td>..</td>, string <tr>
     * @return string tr as html
     */
    protected function getMxtrAsString($trMixed)
    {
        $tr = "";
        //si es un objeto tipo helper
        if(is_object($trMixed) && method_exists($trMixed, "getHtml")) {
            $tr .= "\t".$trMixed->getHtml();
        }
        //array de tds array("<td>...</td>","<td>..</td>",...)
        elseif(is_array($tr)) {
            $tr .= "\t<tr>".implode("\n", $trMixed)."</tr>";
        } else {//string tipo <tr>...</tr>
            $tr .= "\t".$trMixed;
        }
        return $tr;
    }

    protected function buildThead($posHead = array())
    {
        $tr = "";
        foreach($posHead as $pos) {
            $trMixed = $this->objTrs[$pos];
            $tr .= $this->getMxtrAsString($trMixed);
        }
        $thead = "";
        if($tr != "") {
            $thead = "<thead id=\"tblh\">\n$tr</thead>\n";
        }

        return $thead;
    }

    protected function buildTbody($posBody = array())
    {
        $tr = "";
        foreach($posBody as $pos) {
            $trMixed = $this->objTrs[$pos];
            $tr .= $this->getMxtrAsString($trMixed);
        }
        $tbody = "";
        if($tr != "") {
            $tbody = "<tbody id=\"tblb\">\n$tr</tbody>\n";
        }

        return $tbody;
    }

    protected function buildTfoot($posFoot = array())
    {
        $tr = "";
        foreach($posFoot as $pos) {
            $trMixed = $this->objTrs[$pos];
            $tr .= $this->getMxtrAsString($trMixed);
        }
        $tfoot = "";
        if($tr != "") {
            $tfoot = "<tfoot id=\"tblf\">\n$tr</tfoot>\n";
        }

        return $tfoot;
    }

    protected function getPositionsHead()
    {
        $return = array();
        if($this->useThead) {
            foreach($this->objTrs as $i => $row) {
                if(is_object($row) && $row->isRowhead()) {
                    $return[] = $i;
                }
                //array tds
                elseif(is_array($row)) {
                    $isHead = false;
                    foreach($row as $td) {
                        if(is_string($td) && strstr($td, "</th>")) {
                            $isHead = true;
                            break;
                        }
                    }
                    if($isHead) {
                        $return[] = $i;
                    }
                }
                //string
                elseif(is_string($row) && strstr($row, "</th>")) {
                    $return[] = $i;
                } else { //Es objeto y no es rowhead
                    //bug($mxRow);
                }
            }
        }//foreach arObjTrs
        return $return;
    }

    protected function getPositionsBody($posHead = array(), $posFoot = array())
    {
        $return = array();
        //TODO solo usar indices
        //foreach($this->arObjTrs as $i=>$oTr)
        $rows = count($this->objTrs);
        for($i = 0; $i < $rows; $i++) {
            if(!in_array($i, $posHead) && !in_array($i, $posFoot)) {
                $return[] = $i;
            }
        }

        return $return;
    }

    protected function getPositionsFoot()
    {
        $return = array();
        if($this->useTfoot) {
            foreach($this->objTrs as $i => $row) {
                //la unica forma de saber si es pie es que sea un objeto sino no
                if(is_object($row) && $row->isRowfoot()) {
                    $return[] = $i;
                }
            }
        }

        return $return;
    }
    //**********************************
    //             SETS
    //**********************************
    public function useHeader($isOn = true)
    {
        $this->useThead = $isOn;
    }
    public function useFooter($isOn = true)
    {
        $this->useTfoot = $isOn;
    }
    public function setObjrows($objArray = array())
    {
        $this->objTrs = $objArray;
        $this->numRows = count($this->objTrs);
        $this->loadNumcols();
    }

    /**
     * string: "<tr>...</tr>"
     * object: Any object with get_html method
     * array: array(0=>"<td>...</td>",1=>"<td>...</td>"...)
     * @param string|object|array
     */
    public function addObjrow($value)
    {
        $this->objTrs[] = $value;
        $this->numRows = count($this->objTrs);
        $this->loadNumcols();
    }

    public function addTr(HelperTableTr $tr)
    {
        $this->objTrs[] = $tr;
        $this->numRows = count($this->objTrs);
        $this->loadNumcols();
    }

    //**********************************
    //             GETS
    //**********************************
    public function getObjrows()
    {
        return $this->objTrs;
    }

}
