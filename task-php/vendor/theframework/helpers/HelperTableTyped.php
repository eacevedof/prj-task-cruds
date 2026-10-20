<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.4.0
 * @name HelperTableTyped
 * @date 22-10-2016 14:48 (SPAIN)
 * @file HelperTableTyped.php
 * @requires:
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

import_helper("table_basic,anchor,input_text,checkbox");

class HelperTableTyped extends HelperTableBasic
{
    protected $columnsAnchor;
    protected $columnsInputText;

    protected $columnsRadio;
    protected $columnsCheckbox;
    protected $columnsSelect;
    //protected $arSelectOptions;
    protected $columnsRaw;
    protected $isColumnButtonUpdate;
    protected $isColumnButtonInsert;

    public function __construct($rows = array(), $columns = array(), $formId = "frmList", $module = "")
    {
        //1:table,innert,classes,extra,style
        //2:arDataRows,arColumns,sFormId,_idprefix,_id,sModule,sUrlDel,sUrlUp
        parent::__construct();
        $this->lowerFieldnames($rows);
        $this->dataRows = $rows;
        $this->numRows = count($rows);
        //bug($arRows);
        $this->columns = $columns;
        $this->numCols = count($columns);
        $this->formId = $formId;
        $this->_idprefix = "tbl";
        $this->_id = $module;
        $this->mergeGlue = ",";

        $this->columnsAnchor = array();
        $this->columnsInputText = array();
        $this->columnsRadio = array();
        $this->columnsSelect = array();
        $this->columnsCheckbox = array();
    }

    public function getHtml()
    {
        $this->useThead = true;
        $this->useTfoot = true;

        $htmlToReturn = "";
        $fieldset = new HelperFieldset();
        $form = new HelperForm($this->formId);
        //@TODOTEMPLATE
        $form->addClass("form-horizontal");
        //@TODOTEMPLATE
        $form->addStyle("margin:0;padding:0;border:0;");

        $htmlToReturn .= $form->getOpentag();
        $htmlToReturn .= $fieldset->getOpentag();
        //Los campos que se mostrarán antes del listado
        $htmlToReturn .= $this->getFieldsAsString();
        $htmlToReturn .= $fieldset->getClosetag();
        //Barra de navegacion por paginas
        if($this->isPaginateBar) {
            $htmlToReturn .= $this->buildPaginateBar();
        }
        //crea la etiqueta table
        $htmlToReturn .= $this->getOpentag();
        //Filas.  Se carga en la propiedad arObjTrs de tfw_helper se utiliza el metodo tr_as_string (tablerow as string)
        //para imprimir en formato cadena el array de objetos de tipo Tr
        $this->loadArrayObjectTr();
        $htmlToReturn .= $this->getHtmlRows();
        //Fin Filas
        $htmlToReturn .= $this->getClosetag();
        $htmlToReturn .= $this->buildHiddenFields();
        $htmlToReturn .= $form->getClosetag();
        //Fin formulario
        $htmlToReturn .= $this->buildJs();
        return $htmlToReturn;
    }

    protected function buildCellContent($row, $fieldName, $numRow, $numColumn)
    {
        $tdInner = "";
        if($numColumn === 0) {
            $tdInner .= $this->buildHiddenRowchange($numRow);
            $tdInner .= $this->buildHiddenRow($numRow);
            $tdInner .= $this->buildHiddenKeys($row, $numRow);
            $tdInner .= $this->buildHiddenColumns($row, $numRow);
            if($this->hiddenColumns) {
                $tdInner .= $this->buildExtraHidden($numRow);
            }
        }

        //corrige los valores que van dentro de <td>. Leen this->arColumnLngth
        $this->fixLength($row, $fieldName);

        if(isset($row[$fieldName])) {
            $row[$fieldName] = htmlentities($row[$fieldName]);
        }

        switch($fieldName) {
            //columna delete single
            case "delete":
                $tdInner .= $this->buildDeleteButton($row);
                break;
            case "quarantine":
                $tdInner .= $this->buildQuarantineButton($row);
                break;
            case "detail"://detail
                $tdInner .= $this->buildDetailButton($row);
                break;
            case "butinsert"://ejecuta js: save_new
                $tdInner .= $this->buildNewButton($numRow, $numColumn);
                break;
            case "butupdate"://ejecuta js: save_edit
                $tdInner .= $this->buildEditButton($numRow, $numColumn);
                break;
            case "multipick":
                $tdInner .= $this->buildMultipleButton($row, $numRow);
                break;
            case "singlepick":
                $tdInner .= $this->buildSingleButton($row, $numRow);
                //bug($sTdInner,"tdinner");
                break;
            default:
                //Columnas de datos. Puede ser texto plano o en un control
                //14-09-2015 Hago este cambio para que me cargue los campos hidden no solo en la columna de checks
                //$sTdInner = "";
                if(in_array($fieldName, array_keys($this->columnsAnchor))) {
                    $tdInner .= $this->buildAnchorCellContent($row, $fieldName, $numRow, $numColumn);
                } elseif(in_array($fieldName, array_keys($this->columnsInputText))) {
                    $tdInner .= $this->buildInputtextCellContent($row, $fieldName, $numRow, $numColumn);
                } elseif(in_array($fieldName, array_keys($this->columnsSelect))) {
                    $tdInner .= $this->buildSelectCellContent($row, $fieldName, $numRow, $numColumn);
                } elseif(in_array($fieldName, array_keys($this->columnsCheckbox))) {
                    $tdInner .= $this->buildCheckboxCellContent($row, $fieldName, $numRow, $numColumn);
                } elseif(in_array($fieldName, array_keys($this->columnsRaw))) {
                    $tdInner .= $this->buildRawCellContent($row, $fieldName, $numRow, $numColumn);
                } else {
                    $tdInner .= $this->getFieldvalueByName($row, $fieldName);
                }
                break;
        }//fin switch fieldname
        return $tdInner;
    }

    protected function getOperationColumns()
    {
        $columns = parent::getOperationColumns();
        if($this->isColumnButtonUpdate) {
            $columns["butupdate"] = "Save";
        }
        if($this->isColumnButtonInsert) {
            $columns["butinsert"] = "New";
        }
        return $columns;
    }

    protected function buildAnchorCellContent($row, $fieldName, $numRow, $numColumn)
    {
        $cellPos = $numRow."_$numColumn";
        $anchorData = $this->getAnchorData($row, $fieldName);
        //bug($arAnchorData,"build_anchor_cell_content");
        $href = $anchorData["href"];
        //tag para evitar que cree botones donde no hay enlaces
        if($href !== "%nohref%") {
            $anchor = new HelperAnchor();
            $href = str_replace("%cellpos%", "'$cellPos'", $href);
            if(!$anchorData["external"]) {
                if($this->isPermaLink) {
                    $href = "/".$href;
                } else {
                    $href = "index.php?".$href;
                }
            }

            $anchor->setHref($href);

            $target = $anchorData["target"];
            if(!$target) {
                $target = "self";
            }
            $anchor->setTarget($target);
            $anchor->addExtras("cellpos", $cellPos);

            $class = $anchorData["class"];
            if($class) {
                $anchor->addClass($class);
            }

            $innerHtml = $anchorData["innerhtml"];
            $classIcon = $anchorData["icon"];
            //@TODOTEMPLATE
            if($classIcon) {
                $innerHtml = "<span class=\"$classIcon\"></span> $innerHtml";
            }
            $anchor->setInnerhtml($innerHtml);
            return $anchor->getHtml();
        }
        return "-";
    }

    protected function buildInputtextCellContent($row, $fieldName, $numRow, $numColumn)
    {
        $inputText = new HelperInputText();
        $cellPos = $numRow."_$numColumn";
        $inputText->addExtras("cellpos", $cellPos);
        $cellPos = "$fieldName"."_$numRow"."_$numColumn";
        $inputText->setId("txt$cellPos");
        $inputText->setName("txt$cellPos");

        //PROPERTIES
        $properties = $this->columnsInputText[$fieldName];
        //bug($arProperties);
        if($properties["class"]) {
            $inputText->addClass($properties["class"]);
        }
        //@TODOTEMPLATE
        else {
            $inputText->addClass("input-small");
        }
        if($properties["onclick"]) {
            $inputText->setJsOnclick($properties["onclick"]);
        }
        if($properties["onfocus"]) {
            $inputText->setJsOnfocus($properties["onfocus"]);
        }
        if($properties["readonly"]) {
            $inputText->readonly();
        }
        $inputText->setValue($this->getFieldvalueByName($row, $fieldName));
        return $inputText->getHtml();
    }

    protected function buildSelectCellContent($row, $fieldName, $numRow, $numColumn)
    {
        $select = new HelperSelect();
        //$oInputText->add_style("margin:0");
        $cellPos = $numRow."_$numColumn";
        $select->addExtras("cellpos", $cellPos);
        $cellPos = "$fieldName"."_$numRow"."_$numColumn";
        $select->setId("sel$cellPos");
        $select->setName("sel$cellPos");
        $select->setOptions($this->getSelectOptions($fieldName));
        $select->setValueToSelect($this->getFieldvalueByName($row, $fieldName));
        return $select->getHtml();
    }

    protected function buildCheckboxCellContent($row, $fieldName, $numRow, $numColumn)
    {
        $checkbox = new HelperCheckbox();
        $cellPos = $numRow."_$numColumn";
        $checkbox->addExtras("cellpos", $cellPos);
        $cellPos = "$fieldName"."_$numRow"."_$numColumn";
        $checkbox->setId("chk$cellPos");
        $checkbox->setName("chk$fieldName");
        $checkbox->setOptions($this->getKeysAsString($row));

        $fieldValue = $this->getFieldvalueByName($row, $fieldName);
        //bug($sFieldValue,"$sFieldName");
        $properties = $this->columnsCheckbox[$fieldName];
        if(is_array($properties) && in_array("forchecked", array_keys($properties))) {
            if($properties["forchecked"] == $fieldValue) {
                $checkbox->setValuesToCheck($this->getKeysAsString($row));
            }
        }
        //si no hay valor configurado para marcado siempre que exista un valor que no sea "falsi" se da por seleccionado
        elseif($fieldValue) {
            $checkbox->setValuesToCheck($this->getKeysAsString($row));
        }

        return $checkbox->getHtml();
    }

    protected function buildRawCellContent($row, $fieldName, $numRow, $numColumn)
    {
        $column = $this->columnsRaw[$fieldName];
        if(is_string($column)) {
            $this->replaceTagnames($column, $row);
        } elseif(is_object($column)) {
            if(method_exists($column, "getHtml")) {
                $column = $column->getHtml();
                //bug($mxColumn,"mxColum");bug($arRow,"build_raw_cell_content");die;
                $this->replaceTagnames($column, $row);
                //bug($mxColumn,"mxColumn");die;
            }
        }
        $column = str_replace("%numrow%", $numRow, $column);
        $column = str_replace("%numcolum%", $numColumn, $column);
        //bug($mxColumn,"stringed"); bug($arRow,"row");die;
        return $column;
    }

    protected function buildNewButton($numRow, $numColumn)
    {
        $button = new HelperButtonBasic();
        //$oInputText->add_style("margin:0");
        $cellPos = "$numRow"."_$numColumn";
        $button->setId("butInsert$cellPos");
        $button->setInnerhtml("Save");
        $button->setJsOnclick("alert('new');");
        //@TODOTEMPLATE
        $button->addClass("btn btn-alt btn-success");
        $button->addExtras("cellpos", $cellPos);
        return $button->getHtml();
    }

    protected function buildEditButton($numRow, $numColumn)
    {
        $button = new HelperButtonBasic();
        //$oInputText->add_style("margin:0");
        $cellPos = "$numRow"."_$numColumn";
        $button->setId("butUpdate$cellPos");
        $button->setInnerhtml("Save");
        //@TODOTEMPLATE
        $button->addClass("btn btn-alt btn-success");
        $button->setJsOnclick("alert('TODO: Hi! I gonna save you');");
        $button->addExtras("cellpos", $cellPos);
        return $button->getHtml();
    }

    protected function getAnchorData($row, $fieldName)
    {
        $anchorData = array("href" => "#","innerhtml" => "");
        $configData = $this->columnsAnchor[$fieldName];
        //bug($arConfigData,"arConfigData");
        //Pruebo extraer un valor de la columna guardada en href
        $anchorData["href"] = $this->getFieldvalueByName($row, $configData["href"]);
        if(!$anchorData["href"]) {
            $anchorData["href"] = $configData["href"];
        }

        //bug($arAnchorData["href"],"get_anchor_data(),href");
        if(isset($_GET["tfw_iso_language"]) && !(strstr($anchorData["href"], "http") || strstr($anchorData["href"], "javascript:"))) {
            $anchorData["href"] = "{$_GET["tfw_iso_language"]}/{$anchorData["href"]}";
        }
        //bug($arAnchorData["href"],"get_anchor_data(),href 2");
        //Pruebo extraer un valor de la columna guardada en href
        $anchorData["innerhtml"] = $this->getFieldvalueByName($row, $configData["innerhtml"]);
        if(!$anchorData["innerhtml"]) {
            $anchorData["innerhtml"] = $configData["innerhtml"];
        }

        if($configData["external"]) {
            $anchorData["external"] = $configData["external"];
        }
        if($configData["target"]) {
            $anchorData["target"] = $configData["target"];
        }

        if($configData["class"]) {
            $anchorData["class"] = $configData["class"];
        }

        if($configData["icon"]) {
            $anchorData["icon"] = $configData["icon"];
        }
        return $anchorData;
    }//fin get_anchor_data

    protected function replaceTagnames(&$value, $row)
    {
        $tagNames = array();
        //busca todas las coincidencias %value%
        preg_match_all("/%[a-z,A-Z,\_]+%/", $value, $tagNames);
        $tagNames = $tagNames[0];
        foreach($tagNames as $i => $tag) {
            $tagNames[$i] = str_replace("%", "", $tag);
        }

        foreach($tagNames as $fieldName) {
            $tmpFind = "%$fieldName%";
            $fieldValue = $this->getFieldvalueByName($row, $fieldName);
            if($fieldName !== null) {
                $value = str_replace($tmpFind, $fieldValue, $value);
            }
        }
    }

    protected function getSelectOptions($fieldName)
    {
        return $this->columnsSelect[$fieldName];
    }
    //**********************************
    //             SETS
    //**********************************
    /** Tanto innerhtml como href se pueden recuperar desde un campo concreto añadiendole el nombre del campo a estas claves
     * @param array $arColumns ej: array("url_lines"=>array("href"=>"value or fieldname","innerhtml"=>"value or fieldname")
     */
    public function setColumnAnchor($columns)
    {
        $this->columnsAnchor = $columns;
    }
    public function setColumnText($columns)
    {
        $this->columnsInputText = $columns;
    }
    public function setColumnSelect($columns)
    {
        $this->columnsSelect = $columns;
    }
    public function setColumnCheckbox($columns)
    {
        $this->columnsCheckbox = $columns;
    }
    public function setInsertButton($isOn = true)
    {
        $this->isColumnButtonInsert = $isOn;
    }
    public function setUpdateButton($isOn = true)
    {
        $this->isColumnButtonUpdate = $isOn;
    }
    public function setColumnRaw($columns)
    {
        $this->columnsRaw = $columns;
    }
    //**********************************
    //             GETS
    //**********************************

}
