<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.8.0
 * @name HelperTableBasic
 * @date 18-02-2017 13:06 (SPAIN)
 * @file HelperTableBasic.php
 * @requires
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

import_helper("select,form,form_fieldset,input_hidden,checkbox,table");
class HelperTableBasic extends HelperTable
{
    //Campos a crear antes del listado
    protected $objFields;
    protected $keyFields;
    //Colmnas (Cabecera)
    protected $columns;
    //Filas de datos
    protected $dataRows;
    //protected $arKeysDelete;
    //protected $arKeysDetail;
    //protected $arKeysPick;
    protected $orderBy;
    protected $idForm;
    protected $urlNoview;
    protected $urlDelete;
    protected $urlUpdate;
    protected $urlQuarantine;
    protected $urlPickSingle;
    protected $urlPickMultiple;
    protected $urlPaginate;
    protected $module;

    protected $orderWay;//asc|desc
    protected $isOrdenable;
    protected $isToDetail;//crea columna ir a detalle vista update

    protected $isPickMultiple; //crea columna con checks
    protected $isPickSingle; //de momento no tiene efectos
    protected $doMergePkeys;
    protected $isMergeKeyfields;
    protected $mergeGlue;
    protected $assignSingle; //crea funcion js que permite lanzar popup con filas a seleccionar
    protected $assignMulti; // crea funcion js "
    protected $multiAdd; //funcion que ejecuta el post para realizar el proceso de asignacion
    protected $singleAdd; //funcion que ejecuta traspaso de valores por js a la ventana padre
    protected $extraColumns;
    protected $hiddenColumns;
    protected $extraHidden;

    protected $configColTypes;//los distintos formatos de las columnas. Fecha, hora4 hora6,fechahora4, fechahora6, decim
    protected $isDeleteSingle;//crea columna con boton eliminacion single
    protected $isQuarantineSingle;
    //arColumns=array(fieldname=>label);

    //INFO PAGINATE
    protected $infoNumRegs;
    protected $infoCurrentPage;
    protected $infoNumPages;
    protected $infoNextPage;
    protected $infoPreviousPage;
    protected $infoFirstPage;
    protected $infoLastPage;
    protected $itemsPerPage;

    //rowcheckclick
    protected $isCheckOnRowclick;
    protected $isPaginateBar;

    protected $tmpJs;
    protected $columnLength = [];

    public function __construct($rows = [], $columns = [], $idForm = "frmList", $module = "")
    {
        //table,innert,classes,extra,style, parent=HelperTable
        //Mientras que helper table trabaja con $arObjTr HelperTableBasic Lo hace solo con array de datos
        //con estos array de datos posteriormente reutiliza arobjtr
        parent::__construct();
        $this->isPaginateBar = true;
        $this->lowerFieldnames($rows);
        $this->dataRows = $rows;
        $this->numRows = count($rows);
        $this->columns = $columns;
        $this->numCols = count($columns);
        $this->idForm = $idForm;
        $this->_idprefix = "tbl";
        $this->_id = $module;
        $this->module = $module;
        //crea las urls que se guardaran en campos hidden para quarantine, delete, update
        $this->loadGetUrls();
        $this->mergeGlue = ",";
        $this->orderBy = [];
        $this->orderWay = [];
        //bug($this->isPermaLink,"ispermalink en contruct"); die;
    }

    public function getHtml()
    {
        $this->useThead = true;
        $this->useTfoot = true;

        $htmlToReturn = "";
        $fieldset = new HelperFieldset();
        $form = new HelperForm($this->idForm);
        $form->addClass("form-horizontal");
        $form->addStyle("margin:0;padding:0;border:0;");

        $htmlToReturn .= $form->getOpentag();
        $htmlToReturn .= $fieldset->getOpentag();
        //"FILTROS"
        //Los campos que se mostrarán antes del listado. Suele ser para filtrs
        $htmlToReturn .= $this->getFieldsAsString();
        $htmlToReturn .= $fieldset->getClosetag();
        //Barra de navegacion por paginas
        if($this->isPaginateBar) {
            $htmlToReturn .= $this->buildPaginateBar();
        }
        //crea la etiqueta table
        $htmlToReturn .= $this->getOpentag();
        //Filas.  Se carga en la propiedad arObjTrs de tfw_helper se utiliza el metodo tr_as_string
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

    protected function getFieldsAsString()
    {
        $htmlString = "";
        foreach($this->objFields as $objField) {
            $htmlString .= $objField->getHtml();
        }
        return $htmlString;
    }

    protected function buildPaginateBar()
    {
        //errorson();
        $pages = [];
        for($i = 1; $i <= $this->infoNumPages; $i++) {
            $pages[$i] = "pag $i";
        }

        //bug($this->iInfoCurrentPage);
        $selPages = new HelperSelect($pages, "selPage");
        $selPages->setValueToSelect($this->infoCurrentPage);
        //$oSelPages->add_class("span2");
        $selPages->addStyle("margin:0;padding:0;width:85px;");
        $selPages->setName("selPage");
        $selPages->setJsOnchange("table_frmsubmit();");

        $htmlSelect = $selPages->getHtml();
        $htmlNavPages =
        "
        <table id=\"tblNavPages\" style=\"width:100%; padding:0; margin-bottom:3px; margin-top:3px;\">
        <tr>
        <td style=\"background:#fff; padding:0; color:#003399;\">
        <div class=\"pagination pagination-left\" style=\"padding:0;margin:0; margin-left:3px;\">";
        $htmlNavPages .= $this->buildNavigationButtons($htmlSelect);
        $htmlNavPages .= "</div>    
        </td>
        </tr>
        </table>
        ";
        return $htmlNavPages;
    }//build_paginate_bar

    /*
     * Para single return solo se envia el dato por js.
     * Necesito recuperar
     */
    protected function getReturnSingle()
    {
        //if(sIdNameKey) sUrlAction += \"&returnkey=\"+sIdNameKey;
        //if(sIdNameDesc) sUrlAction += \"&returndesc=\"+sIdNameDesc;
        $js = [];
        $idReturnKey = $this->get_get("returnkey");
        $idReturnDesc = $this->get_get("returndesc");
        $doClose = (int)$this->get_get("close");
        $js[] = "var sIdReturnKey=\"$idReturnKey\";";
        $js[] = "var sIdReturnDesc=\"$idReturnDesc\";";
        $js[] = "var doClose=$doClose;";
        return implode("\n", $js);
    }

    protected function convertToUrlparams($forParams)
    {
        $urls = [];
        $glue = "&";
        if($this->isPermaLink) {
            $glue = "/";
            foreach($forParams as $params) {
                foreach($params as $fieldName => $value) {
                    $urls[] = $value;
                }
            }
        } else {
            foreach($forParams as $params) {
                foreach($params as $fieldName => $value) {
                    $urls[] = "$fieldName=$value";
                }
            }
        }
        $url = implode($glue, $urls);
        return $url;
    }

    protected function jsFnMultiassignWindow()
    {
        $js = "
        function multiassign_window(sUrlAction,iW,iH,doClose)
        {
            var iW = iW || 800;
            var iH = iH || 600;
            var sUrlAction = sUrlAction || window.location.search;
            window.open(sUrlAction,\"multipick\",\"width=\"+iW+\",height=\"+iH,status=0,scrollbars=0,resizable=0,left=0,top=0);
        }
        ";
        return $js;
    }//js_fn_multiassign_window

    protected function jsFnSingleassignWindow()
    {
        $this->setTmpjs();
        if($this->isPermaLink) {
            $this->addTmpjs("window.open(\"/\"+sUrlAction,\"singlepick\",\"width=\"+iW+\",height=\"+iH,status=0,scrollbars=0,resizable=0,left=0,top=0);");
        } else {
            $this->addTmpjs("window.open(\"index.php?\"+sUrlAction,\"singlepick\",\"width=\"+iW+\",height=\"+iH,status=0,scrollbars=0,resizable=0,left=0,top=0);");
        }

        $tmpJs = $this->getTmpjs();
        $js .= "
        function singleassign_window(sUrlAction,sIdNameKey,sIdNameDesc,iW,iH)
        {
            var iW = iW||800;
            var iH = iH||600;
            var sUrlAction = sUrlAction || window.location.search;
            $tmpJs
        }
        ";
        return $js;
    }

    protected function jsFnMultiadd()
    {
        $js = "
        //helper_table_basic
        function multiadd(sUrlAction,sIdForm)
        {
            var sIdForm = sIdForm || \"$this->idForm\"; 
            var sUrlAction = sUrlAction || window.location.search;
            var oForm = document.getElementById(sIdForm);
            if(oForm)
            {
                if(is_checked(\"pkeys[]\"))
                {    
                    if(confirm(oTfwtr.confirm))
                    {
                        oForm.action=sUrlAction;
                        oForm.submit();
                    }
                }
                else
                    alert(oTfwtr.norows);
            }
        }
        ";
        return $js;
    }

    protected function jsFnSingleadd()
    {
        $js = "
        //helper_table_basic
        function singleadd(iRow,sIdReturnKey,sIdReturnDesc,doClose)
        {
            var sHidKey = \"hidKeySingle_\"+iRow;
            var sHidDesc = \"hidDescSingle_\"+iRow;
            //window from: top: popup, opener:parent window la ventana que llama al popup
            var oWindowParent = top.opener;
            if(oWindowParent)
            {
                var sKeyValue = document.getElementById(sHidKey).value;
                var sDescValue = document.getElementById(sHidDesc).value;
                var eInput = oWindowParent.document.getElementById(sIdReturnKey);
                if(!eInput) eInput = oWindowParent.document.getElementsByName(sIdReturnKey)[0];
                if(eInput) eInput.value = sKeyValue;
                eInput = oWindowParent.document.getElementById(sIdReturnDesc);
                if(!eInput) eInput = oWindowParent.document.getElementsByName(sIdReturnDesc)[0];
                if(eInput) eInput.value = sDescValue;
                //cierra el popup
                if(doClose) self.close();
            }
        }
        ";
        return $js;
    }

    protected function jsFnFormSubmit()
    {
        //OJO CON ESTO es un parche. Tengo que ver pq ya no es util concatenar en la url la página
        //sin permalnk no se pasa page en la url ya que se envia selpage por post y en los even_postandget se genera
        //el $_GET[page]. En permalink no me vale ya que tengo que pasar la pagina en la url para que pueda ser interpretado
        //por el router
        $tmpJs = "";
        if($this->isPermaLink) {
            $this->setTmpjs();
            $this->addTmpjs("var iPage = TfwControl.get_value_by_id(\"selPage\");");
            $this->addTmpjs("iPage = iPage || 1;");
            $this->addTmpjs("sUrlAction += iPage + \"/\";");
            $tmpJs = $this->getTmpjs();
        }

        $js = "    
        //helper_table_basic
        function table_frmsubmit(sUrlAction,sIdForm)
        {            
            var sIdForm = sIdForm || \"$this->idForm\"; 
            var sUrlAction = sUrlAction || document.getElementById(\"hidUrlPaginate\").value;
            var oForm = document.getElementById(sIdForm);
            if(oForm)
            {
                $tmpJs
                oForm.action=sUrlAction;
                oForm.submit();
            }
         }
        ";
        return $js;
    }

    protected function jsFnRowcheck()
    {
        $js = "
        //helper_table_basic
        function rowcheck(iRow,id)
        {
            var id = id||\"pkeys\";
            id = id+\"_\"+iRow;
            //alert(id);return;
            var eCheckBox = document.getElementById(id);
            //alert(eCheckBox);
            if(eCheckBox)
            {
                if(TfwControl.is_checkbox_checked(eCheckBox))
                    TfwControl.set_checkbox_check(eCheckBox,0);
                else
                    TfwControl.set_checkbox_check(eCheckBox,1);
                rowchange(iRow);
            }
        }
        ";
        return $js;
    }

    protected function jsFnCheckAll()
    {
        $js = "
        //helper_table_basic
        function check_all(id,name)
        {
            var id = id||\"pkeys_all\";
            var name = name||\"pkeys[]\";
            var eCheckBox = document.getElementById(id);
            //alert(eCheckBox);
            if(TfwControl.is_checkbox_checked(eCheckBox))
                TfwControl.set_checked_by_name(name,true);
            else
                TfwControl.set_checked_by_name(name,false);
        }
        ";
        return $js;
    }

    protected function jsFnNavClick()
    {
        $this->setTmpjs();
        if($this->isPermaLink) {
            $this->addTmpjs("sUrlAction += iPage+\"/\";");
        } else {
            $this->addTmpjs("sUrlAction += \"&page=\"+iPage;");
        }

        $tmpJs = $this->getTmpjs();

        $js = "
        //helper_table_basic
        function nav_click(iPage)
        {
            var iPage = iPage || 1;
            var sUrlAction = TfwControl.get_value_by_id(\"hidUrlPaginate\");
            $tmpJs
            TfwControl.sel_option_by_id(\"selPage\",iPage);
            TfwControl.form_submit(\"$this->idForm\",sUrlAction);
        }
        ";
        return $js;
    }

    protected function jsFnMultiDelete()
    {
        $checkPostKey = $this->buildPostkey();
        $js = "    
        //helper_table_basic
        function multi_delete()
        {
            if(is_checked(\"$checkPostKey"."[]\"))
            {
                if(confirm(\"".tr_main_confirm_before_delete."\"))
                {   
                    var sUrlAction = document.getElementById(\"hidUrlNoview\").value;
                    sUrlAction = sUrlAction.replace(\"%replaceview%\",\"delete\");
                    var oHidAction = document.getElementById(\"hidAction\");
                    oHidAction.value = \"multidelete\";
                    TfwControl.form_submit(\"$this->idForm\",sUrlAction);
                }
            }
            else
                alert(\"".tr_main_table_selection."\");
        }
        ";
        return $js;
    }

    protected function jsFnMultiQuarantine()
    {
        $checkPostKey = $this->buildPostkey();

        $js = "
        //helper_table_basic
        function multi_quarantine()
        {
            if(is_checked(\"$checkPostKey"."[]\"))
            {
                if(confirm(\"".tr_main_confirm_before_quarantine."\"))
                {    
                    var sUrlAction = document.getElementById(\"hidUrlNoview\").value;
                    sUrlAction = sUrlAction.replace(\"%replaceview%\",\"quarantine\");
                    var oHidAction = document.getElementById(\"hidAction\");
                    oHidAction.value = \"multiquarantine\";                    
                    TfwControl.form_submit(\"$this->idForm\",sUrlAction);
                }
            }
            else
                alert(\"".tr_main_table_selection."\");
        }
        ";
        return $js;
    }

    protected function jsFnIsChecked()
    {
        $js = "
        //helper_table_basic
        function is_checked(sCheckName)
        {
            var arObjChecks = document.getElementsByName(sCheckName);
            //bug(arObjChecks);
            if(arObjChecks.length!=undefined)
            {
                for(var i=0; i<arObjChecks.length; i++)
                    if(arObjChecks[i].checked==1)
                        return true;
            }
            return false;
        }
        ";
        return $js;
    }

    protected function jsFnRowchange()
    {
        $js = "
        //helper_table_basic
        function rowchange(iRow)
        {
            var sId = \"hidRowChanged_\"+iRow+\"_0\";
            var oHidRowChanged = document.getElementById(sId);
            //alert(oHidRowChanged);
            if(oHidRowChanged) oHidRowChanged.value=1;
        }
        ";
        return $js;
    }

    protected function jsFnHrefgo()
    {
        $js = "
        //helper_table_basic
        function hrefgo(sUrl,sConfirm)
        {
            var sConfirm = sConfirm || \"\";
            var sUrl = sUrl || \"\";
            if(sUrl!=\"\")
            {
                if(sConfirm!=\"\")
                {
                    if(confirm(sConfirm))
                        window.location = sUrl;
                }
                else
                {
                    window.location = sUrl;
                }
            }
        }
        ";
        return $js;
    }

    protected function buildJs()
    {
        $htmlJs = "<script helper=\"tablebasic.build_js\" type=\"text/javascript\">\n";
        $htmlJs .= $this->jsFnRowchange();
        $htmlJs .= $this->jsFnFormSubmit();
        $htmlJs .= $this->jsFnCheckAll();
        $htmlJs .= $this->jsFnNavClick();
        $htmlJs .= $this->jsFnMultiDelete();
        $htmlJs .= $this->jsFnMultiQuarantine();
        $htmlJs .= $this->jsFnIsChecked();
        $htmlJs .= $this->jsFnHrefgo();
        if($this->isCheckOnRowclick) {
            $htmlJs .= $this->jsFnRowcheck();
        }

        if($this->isOrdenable) {
            $htmlJs .=
        "var sThBackground=\"\";
         var sThColor=\"\";
            
         function order_by(eTh)
         {
            var sFieldName = eTh.getAttribute(\"dbfield\");
            var oHidOrderBy = document.getElementById(\"hidOrderBy\");
            var oHidOrderType = document.getElementById(\"hidOrderType\");
            
            var sOrderBy = oHidOrderBy.value;
            var sOrderType = oHidOrderType.value;
            
            if(sOrderBy)
            {
                if(sFieldName==sOrderBy)
                {
                    if(sOrderType.toUpperCase()==\"ASC\")
                        oHidOrderType.value=\"DESC\";
                    else oHidOrderType.value=\"ASC\";
                }
                else
                {
                    oHidOrderBy.value = sFieldName;
                    oHidOrderType.value = \"ASC\";
                }
            }
            else
            {
                oHidOrderBy.value = sFieldName;
                oHidOrderType.value = \"ASC\";                
            }
            //bug('antes de submit');
            table_frmsubmit();
         }
         
         function on_thover(eTh,sColor,sBackColor)
         {
            sThBackground = eTh.style.backgroundColor;
            sThColor = eTh.style.color;
            eTh.style.color=sColor;
            eTh.style.backgroundColor=sBackColor;
         }
         
         function on_thout(eTh){eTh.style.backgroundColor=sThBackground;eTh.style.color=sThColor;}
        ";
        }

        //        //Campos filtros
        //        if($this->arObjFields)
        //        {
        //            $sFieldIds = $this->build_js_field_ids();
        //            $sHtmlJs .="
        //         function reset_filters()
        //         {
        //            var arFields = [$sFieldIds];
        //            TfwFieldValidate.reset(arFields);
        //         }
        //        ";
        //        }//hay arObjFields

        //funcion js para popup
        if($this->assignMulti) {
            $htmlJs .= $this->jsFnMultiassignWindow();
        }
        if($this->multiAdd) {
            $htmlJs .= $this->jsFnMultiadd();
        }
        if($this->assignSingle) {
            $htmlJs .= $this->jsFnSingleassignWindow();
        }
        if($this->singleAdd) {
            $htmlJs .= $this->jsFnSingleadd();
        }
        $htmlJs .= "</script>\n";
        return $htmlJs;
    }

    protected function buildJsFieldIds()
    {
        $ids = array_keys($this->columns);
        $fieldIds = implode("\",\"", $ids);
        $fieldIds = "\"$fieldIds\"";
        return $fieldIds;
    }

    protected function buildHiddenFields()
    {
        $htmlHidden = "";
        $hidden = new HelperInputHidden();

        $hidden->setId("hidOrderBy");
        $hidden->setName("hidOrderBy");
        if($this->orderBy) {
            $hidden->setValue(implode(",", $this->orderBy));
        }
        $htmlHidden .= $hidden->getHtml();

        $hidden->setId("hidOrderType");
        $hidden->setName("hidOrderType");
        if($this->orderWay) {
            $hidden->setValue(implode(",", $this->orderWay));
        }
        $htmlHidden .= $hidden->getHtml();

        $hidden->setId("hidKeyFields");
        $hidden->setName("hidKeyFields");
        if($this->keyFields) {
            $hidden->setValue(implode(",", $this->keyFields));
        }
        $htmlHidden .= $hidden->getHtml();

        $hidden->setName("hidUrlCurrent");
        $hidden->setId("hidUrlCurrent");
        $hidden->setValue($this->get_request_uri());
        $htmlHidden .= $hidden->getHtml();

        //URLS: Solo para js
        $hidden->setName(null);
        $hidden->setId("hidUrlNoview");
        $hidden->setValue($this->urlNoview);
        $htmlHidden .= $hidden->getHtml();

        //bug($this->sUrlPaginate);
        $hidden->setId("hidUrlPaginate");
        $hidden->setValue($this->urlPaginate);
        $htmlHidden .= $hidden->getHtml();

        //creado como caja auxiliar para cualquier logica extra que se haga en la vista
        $hidden->setId("hidAuxiliar");
        $hidden->setName("hidAuxiliar");
        $hidden->setValue("");
        $htmlHidden .= $hidden->getHtml();

        //Necesario para saber si solo se está refrescando, filtrando o eliminando
        $hidden->setId("hidAction");
        $hidden->setName("hidAction");
        $hidden->setValue("");
        $htmlHidden .= $hidden->getHtml();

        $hidden->setId("hidPostback");
        $hidden->setName("hidPostback");
        $hidden->setValue("");
        $htmlHidden .= $hidden->getHtml();

        $hidden->setId("selItemsPerPage");
        $hidden->setName("selItemsPerPage");
        $hidden->setValue($this->itemsPerPage);
        $htmlHidden .= $hidden->getHtml();
        //Si se ha indicado que la tabla es de asignacion
        //se guarda los datos actuales de la url
        if($this->assignSingle) {
            $hidden->setId("hidAssignSingle");
            $hidden->setName("hidAssignSingle");
            $hidden->setValue();
            $htmlHidden .= $hidden->getHtml();
        }
        if($this->assignMulti) {
            $hidden->setId("hidAssignMulti");
            $hidden->setName("hidAssignMulti");
            $hidden->setValue();
            $htmlHidden .= $hidden->getHtml();
        }
        return $htmlHidden;
    }

    protected function buildHiddenRow($numRow)
    {
        $hidden = new HelperInputHidden();
        $idName = "hidRow_$numRow"."_0";
        $hidden->setId($idName);
        $hidden->setName($idName);
        $hidden->setValue($numRow);
        return $hidden->getHtml();
    }

    protected function buildHiddenRowchange($numRow)
    {
        $hidden = new HelperInputHidden();
        $idName = "hidRowChanged_$numRow"."_0";
        $hidden->setId($idName);
        $hidden->setName($idName);
        $hidden->setValue("0");
        return $hidden->getHtml();
    }

    protected function buildHiddenKeys($row, $numRow)
    {
        $htmlHidden = "";
        $hidden = new HelperInputHidden();
        foreach($this->keyFields as $fieldName) {
            $idName = "hid$fieldName"."_$numRow";
            $hidden->setId($idName);
            $hidden->setName($idName);
            $hidden->setValue($this->getFieldvalueByName($row, $fieldName));
            $htmlHidden .= $hidden->getHtml();
        }
        return $htmlHidden;
    }

    protected function buildHiddenColumns($row, $numRow)
    {
        $htmlHidden = "";
        $hidden = new HelperInputHidden();
        if($this->hiddenColumns) {
            foreach($this->hiddenColumns as $fieldName) {
                $idName = "hid$fieldName"."_$numRow"."_0";
                $hidden->setId($idName);
                $hidden->setName($idName);
                $hidden->addExtras("cellpos", $numRow."_0");
                //bug($sFieldName);
                $hidden->setValue($this->getFieldvalueByName($row, $fieldName));
                $htmlHidden .= $hidden->getHtml();
            }
        }
        return $htmlHidden;
    }

    protected function buildExtraHidden($numRow)
    {
        $htmlHidden = "";
        $hidden = new HelperInputHidden();
        foreach($this->extraHidden as $fieldName => $value) {
            $idName = "hid$fieldName"."_$numRow"."_0";
            $hidden->setId($idName);
            $hidden->setName($idName);
            $hidden->addExtras("cellpos", $numRow."_0");

            if(is_array($value)) {
                $value = implode(",", $value);
            }
            $hidden->setValue($value);
            $htmlHidden .= $hidden->getHtml();
        }
        return $htmlHidden;
    }

    /**
     * Crea el array de columnas incluyendo operaciones si las hubiese
     * Segun estas columnas crea la fila de cabecera
     * Segun las columnas crea las filas de datos
     * @return array
     */
    protected function loadArrayObjectTr()
    {
        //Devuelve las columnas de operaciones  Upd,Delete,Multiple
        $columns = $this->getOperationColumns();
        //bug($arColumns,"arcolumns antes");
        //Fila Cabecera. Añade la cabecera según las posiciones de las columnas
        $this->loadHeadRow($columns, $this->objTrs);
        //Filas Cuerpo. Añade las filas restantes según las posiciones de las columnas
        $this->loadBodyRows($columns, $this->objTrs);
    }//load_array_object_tr

    protected function getOperationColumns()
    {
        $columns = [];
        if($this->isToDetail) {
            $columns["detail"] = "Upd";
        }
        if($this->isPickMultiple) {
            $columns["multipick"] = "Multi";
        }
        if($this->isPickSingle) {
            $columns["singlepick"] = "Single";
        }

        if(!empty($columns)) {
            $columns = array_merge($columns, $this->columns);
        } else {
            $columns = $this->columns;
        }

        if($this->isDeleteSingle) {
            $columns["delete"] = "Del";
        }
        if($this->isQuarantineSingle) {
            $columns["quarantine"] = "Del";
        }
        //bug($arColumns,"operation_columns");
        if($this->extraColumns) {
            $columns = $this->reorderColumns($columns, $this->extraColumns);
        }
        return $columns;
    }

    protected function reorderColumns($columns, $virtualColumns)
    {
        $colsReordered = [];
        $occupied = [];
        $temp = [];

        foreach($columns as $fieldName => $label) {
            $position = array_key_position($fieldName, $columns);
            $occupied[] = array("fieldname" => $fieldName,"label" => $label,"position" => $position);
        }

        foreach($occupied as $i => $columnOccupied) {
            $fieldName = $columnOccupied["fieldname"];
            $label = $columnOccupied["label"];
            $position = $columnOccupied["position"];

            $newInPosition = $this->getColumnsByPosition($position, $virtualColumns);
            foreach($newInPosition as $i => $newCol) {
                $temp[] = $newCol;
            }

            $temp[] = $columnOccupied;
            $this->unsetByPosition($position, $virtualColumns);
        }

        //Fuera del rango de los que existian
        foreach($virtualColumns as $i => $data) {
            $data["fieldname"] = "virtual_$i";
            $temp[] = $data;
        }

        foreach($temp as $data) {
            $colsReordered[$data["fieldname"]] = $data["label"];
        }
        //bug($arColsReordered); die;
        return $colsReordered;
    }

    protected function getColumnsByPosition($position, $columns)
    {
        $colsInPosition = [];
        foreach($columns as $i => $colData) {
            if($colData["position"] == $position) {
                $colData["fieldname"] = "virtual_$i";
                $colsInPosition[] = $colData;
            }
        }
        return $colsInPosition;
    }

    protected function unsetByPosition($position, &$colums)
    {
        foreach($colums as $key => $colData) {
            if($colData["position"] == $position) {
                unset($colums[$key]);
            }
        }
    }

    protected function loadHeadRow($columns, &$objRows)
    {
        $trHead = new HelperTableTr();
        $trHead->setAsRowhead();
        $trHead->setAttrRownumber(-1);
        //bug($arColumns,"arColumns");die;
        //Cabecera
        foreach($columns as $fieldName => $label) {
            $columnPosition = array_key_position($fieldName, $columns);
            //bug($sFieldName,$iNumColumn);
            $th = new HelperTableTd();
            $th->setAsHeader();
            $th->setAttrDbfield($fieldName);
            $th->setAttrColnumber($columnPosition);
            $th->setAttrRownumber(-1);
            switch($fieldName) {
                case "multipick":
                    //Al ser multipick es una columna checkbox sin label
                    $th->setInnerhtml($this->buildMultipleButtonHead());
                    break;
                default:
                    $colLabel = $this->buildColumnLabel($fieldName, $label);
                    $th->setInnerhtml($colLabel);
                    //bug($oTh,$sFieldName);
                    //si la tabla permite ordenar se aplica la func js para ejecutar el submit
                    $noOrder = array("delete","detail","singlepick","quarantine");
                    if($this->isOrdenable && !(in_array($fieldName, $noOrder) || strstr($fieldName, "virtual_"))) {
                        $th->setJsOnclick("order_by(this);");
                        $th->setJsOnmouseover("on_thover(this,'#000','#B2B2B2');");
                        $th->setJsOnmouseout("on_thout(this);");
                    }
                    break;
            }//fin switch fieldname
            $objThs[] = $th;
        }
        $trHead->setObjtds($objThs);
        //array con celdas cabecera
        $objRows[] = $trHead;
        //Fin cabecera
    }//fin load_head_row

    protected function buildColumnLabel($fieldName, $label)
    {
        //bug($this->arOrderBy,"arOrdeBy");bug($this->arOrderWay,"arOrderWay");
        $columnHeader = $label;
        if($this->isOrdenable && $this->orderBy) {
            //bug($this->arOrderBy,$sFieldName);bug($this->arOrderWay);
            $position = array_search($fieldName, array_values($this->orderBy));
            //bug($iPosition,"position $sFieldName");
            if($position || $position === 0) {
                $orderWay = $this->orderWay[$position];
            }
            //bug($sOrderWay,"orderway");
            if($orderWay == "ASC") {
                $columnHeader = "$columnHeader <span class=\"awe-caret-up\"></span>";
            } elseif($orderWay == "DESC") {
                $columnHeader = "$columnHeader <span class=\"awe-caret-down\"></span>";
            }
            //else  Aqui deberia dar errores
        }
        //bug($sColumnHeader,"columnHeader: $sFieldName, $sLabel");
        return $columnHeader;
    }//build_column_label

    protected function loadBodyRows($colums, &$objRows)
    {
        //Recorre las filas de datos
        foreach($this->dataRows as $numRow => $row) {
            //Celdas de la fila
            $objTds = [];
            $tr = new HelperTableTr();

            if($this->isCheckOnRowclick) {
                $postKey = $this->buildPostkey();
                $tr->setJsOnclick("rowcheck('$numRow','$postKey');");
                //$oTr->set_js_onchange("alert('changed')");//no escucha este evento
            }

            $tr->setAttrRownumber($numRow);
            //Recorre las columnas segun el orden pasado en las cabeceras
            foreach($colums as $fieldName => $label) {
                $numColumn = (int)array_key_position($fieldName, $colums);
                $td = new HelperTableTd();
                $td->setAttrDbfield($fieldName);
                $td->setAttrColnumber($numColumn);
                $td->setAttrRownumber($numRow);
                $td->setAttrPosition($numRow, $numColumn);
                //CONTENIDO DE CELDA
                $tdInner = $this->buildCellContent($row, $fieldName, $numRow, $numColumn);
                $td->setInnerhtml($tdInner);
                $objTds[] = $td;
            }//fin for arColumns
            $tr->setObjtds($objTds);
            $objRows[] = $tr;
        }//fin for arDataRows
    }//load_body_rows

    /**
     * ___MAIN_CELL__
     * Segun el nombre de la columna, ya sea relacionada con un campo en bd y/o con una operacion agregada
     * se genera su contenido.
     * @param array $arRow Array de pares columna=>valor
     * @param string $sFieldName Nombre de la columna a la que corresponde la celda
     * @param integer $iNumRow Numero de la fila. Se utiliza para crear el atributo de posicion de celda
     * @param integer $iNumColumn Numero de la columna. Se utiliza para crear los hiddenkeys y la posicion de la celda
     * @return string Html con contenido de la celda
     */
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
        $row[$fieldName] = htmlentities($row[$fieldName]);

        switch($fieldName) {
            //columna delete single
            case "delete":
                $tdInner .= $this->buildDeleteButton($row);
                break;
            case "quarantine":
                $tdInner .= $this->buildQuarantineButton($row);
                break;
            case "detail":
                $tdInner .= $this->buildDetailButton($row);
                break;
            case "multipick":
                //bug($oChekbox);
                $tdInner .= $this->buildMultipleButton($row, $numRow);
                break;
            case "singlepick":
                $tdInner .= $this->buildSingleButton($row, $numRow);
                //bug($sTdInner,"tdinner");
                break;
            default:
                $tdInner .= $this->getFieldvalueByName($row, $fieldName);
                break;
        }//fin switch fieldname
        return $tdInner;
    }

    protected function buildUrlButton($urlMethod, $row, $exclude = null)
    {
        $returnUrl = $urlMethod;
        $keys = $this->getKeysAsUrl($row, $exclude);

        if($this->isPermaLink) {
            //si no acaba en / le añado esta
            if(!$this->is_lastchar_slash($returnUrl)) {
                $returnUrl = $urlMethod."/";
            }

            if($keys) {
                $returnUrl .= $keys."/";
            }

        } elseif($keys) {
            $returnUrl .= "&".$keys;
        }

        return $returnUrl;
    }

    protected function buildDeleteButton($row)
    {
        $anchor = new HelperAnchor();
        $anchor->addClass("btn btn-danger");
        //icon-remove-sign
        $anchor->setInnerhtml("\n<span class=\"awe-remove-sign\"></span> ".tr_main_list_delete);
        $anchor->setTarget("self");
        $urlButton = $this->buildUrlButton($this->urlDelete, $row);
        $anchor->setHref("javascript:hrefgo('$urlButton','".tr_main_confirm_before_delete."');");
        $htmlAnchor .= $anchor->getHtml();
        return $htmlAnchor;
    }

    protected function buildQuarantineButton($row)
    {
        $anchor = new HelperAnchor();
        $anchor->addClass("btn btn-danger");
        //icon-remove-sign
        $anchor->setInnerhtml("\n<span class=\"awe-remove-sign\"></span> ".tr_main_list_quarantine);
        $anchor->setTarget("self");
        $urlButton = $this->buildUrlButton($this->urlQuarantine, $row);
        $anchor->setHref("javascript:hrefgo('$urlButton','".tr_main_confirm_before_quarantine."');");
        $htmlAnchor .= $anchor->getHtml();
        return $htmlAnchor;
    }

    protected function buildDetailButton($row)
    {
        $anchor = new HelperAnchor();
        $anchor->addClass("btn btn-info");
        $anchor->setInnerhtml("\n<span class=\"awe-info-sign\"></span> info");
        $anchor->setTarget("self");
        $urlButton = $this->buildUrlButton($this->urlUpdate, $row, "page");
        $anchor->setHref($urlButton);
        $htmlAnchor .= $anchor->getHtml();
        return $htmlAnchor;
    }

    protected function buildMultipleButton($row, $numRow = "")
    {
        $chekbox = new HelperCheckbox();
        $chekbox->setUnlabeled();
        $htmlCheck = "";

        if($this->doMergePkeys) {
            foreach($this->keyFields as $fldKeyName) {
                $values[] = $fldKeyName."=".$this->getFieldvalueByName($row, $fldKeyName);
            }

            $merged = implode($this->mergeGlue, $values);
            $chekbox->setOptions(array($merged => null));
            $chekbox->setId("pkeys_$numRow");
            $chekbox->setName("pkeys");
            $chekbox->setAttrDbfield("pkeys");
            //$oChekbox->set_js_onchange("alert('hola')");
            $htmlCheck .= $chekbox->getHtml();
        } else {
            //TODO: Esto habría que mejorarlo. Se debe añadir el atributo
            //name por js cuando se active el check y quitarlo cuando se desactiva
            //para evitar dos check en una celda Así pues cuando se envie el formulario
            //llegaran por post solo los hidden con nombre
            foreach($this->keyFields as $fldKeyName) {
                $fieldValue = $this->getFieldvalueByName($row, $fldKeyName);
                $chekbox->setOptions(array($fieldValue => ""));
                $id = $fldKeyName;
                if($numRow !== "") {
                    $id .= "_$numRow";
                }
                $chekbox->setId($id);
                //Cambio en el helper Checkbox. No hace falta [] ya que se activa o desactiva según la variable isGrouped. Por defecto true
                $chekbox->setName($fldKeyName);
                $chekbox->setAttrDbfield($fldKeyName);
                //$oChekbox->set_js_onchange("alert('hola')");
                $htmlCheck .= $chekbox->getHtml();
            }
        }
        return $htmlCheck;
    }//build_multiple_button

    protected function buildSingleButton($row, $numRow = "")
    {
        //array("destkey"=>"txtCode","destdesc"=>"txtCodeErp","keys"=>"id","descs"=>"description,bo_login","close"=>1)
        $idDestKey = $this->singleAdd["destkey"];
        $idDestDesc = $this->singleAdd["destdesc"];
        $columnsKeys = explode(",", $this->singleAdd["colkeys"]);
        $columnsDesc = explode(",", $this->singleAdd["coldescs"]);
        $doClose = (int)$this->singleAdd["close"];

        $button = new HelperButtonBasic();
        $button->addClass("btn btn-success");
        $button->setInnerhtml("Pick");
        $hidKey = new HelperInputHidden("hidKeySingle_$numRow");
        $hidDesc = new HelperInputHidden("hidDescSingle_$numRow");

        $button->setJsOnclick("singleadd($numRow,'$idDestKey','$idDestDesc',$doClose);");

        $htmlButton = "";

        $numFields = count($columnsKeys);
        foreach($columnsKeys as $fieldName) {
            if($numFields > 1) {
                $values[] = $fieldName."=".$this->getFieldvalueByName($row, $fieldName);
            } else {
                $values[] = $this->getFieldvalueByName($row, $fieldName);
            }
        }

        $merged = implode($this->mergeGlue, $values);
        $hidKey->setValue($merged);

        $values = [];
        //$iNumFields = count($arColumnsDesc);
        foreach($columnsDesc as $fieldName) {
            $values[] = $this->getFieldvalueByName($row, $fieldName);
        }

        $merged = implode($this->mergeGlue, $values);
        $hidDesc->setValue($merged);

        $htmlButton .= $button->getHtml();
        $htmlButton .= $hidKey->getHtml();
        $htmlButton .= $hidDesc->getHtml();
        return $htmlButton;
    }//build_single_button

    protected function buildMultipleButtonHead()
    {
        $chekbox = new HelperCheckbox();
        $chekbox->setUnlabeled();
        $htmlCheck = "";
        if($this->doMergePkeys) {
            $fldKeyNames = implode($this->mergeGlue, array_values($this->keyFields));
            $chekbox->setOptions(array($fldKeyNames => null));
            $chekbox->setId("pkeys_all");
            $chekbox->setName("pkeys_all");
            $chekbox->setJsOnclick("check_all();");
            $htmlCheck .= $chekbox->getHtml();
        } else {//No es un keymerge entonces se crea una caja por key
            foreach($this->keyFields as $fldKeyName) {
                $chekbox->setOptions(array($fldKeyName => null));
                $chekbox->setId($fldKeyName."_all");
                $chekbox->setName($fldKeyName."_all");
                $chekbox->setJsOnclick("check_all('$fldKeyName"."_all','$fldKeyName"."[]');");
                $htmlCheck .= $chekbox->getHtml();
            }
        }
        return $htmlCheck;
    }

    protected function getKeysAsUrl($row, $exclude = null)
    {
        //Necesario para poder excluir parámetros que no se usan con .htcacces en los links
        //y que dificultan el enrutamiento ej. module=xxx&page=3&update=65.  "page" sobra
        $excludes = $this->mixed_to_array($exclude);
        $rowKeys = [];
        $glue = "&";

        if($this->isPermaLink) {
            foreach($this->keyFields as $fldKeyName) {
                if(in_array($fldKeyName, $excludes)) {
                    continue;
                }
                $fieldValue = $this->getFieldvalueByName($row, $fldKeyName);
                if($fieldValue) {
                    $rowKeys[] = $fieldValue;
                }
            }
        } else {
            foreach($this->keyFields as $fldKeyName) {
                if(in_array($fldKeyName, $excludes)) {
                    continue;
                }
                $fieldValue = $this->getFieldvalueByName($row, $fldKeyName);
                if($fieldValue) {
                    $rowKeys[] = "$fldKeyName=$fieldValue";
                }
            }
        }
        $url = implode($glue, $rowKeys);
        return $url;
    }

    protected function getKeysAsString($row, $exclude = null, $isWithName = 0)
    {
        $rowKeys = [];
        $glue = ",";
        $excludes = $this->mixed_to_array($exclude);
        foreach($this->keyFields as $fldKeyName) {
            if(in_array($fldKeyName, $excludes)) {
                continue;
            }
            $fieldValue = $this->getFieldvalueByName($row, $fldKeyName);
            if($isWithName) {
                $rowKeys[] = "$fldKeyName:$fieldValue";
            } else {
                $rowKeys[] = $fieldValue;
            }
        }
        $keys = implode($glue, $rowKeys);
        return $keys;
    }

    protected function getFieldvalueByName($row, $name)
    {
        foreach($row as $fieldName => $fieldValue) {
            if($fieldName == $name) {
                $fieldValue = $this->getFormatedValue($fieldName, $fieldValue);
                return $fieldValue;
            }
        }
        return null;
    }

    protected function getColformat($fieldName)
    {
        foreach($this->configColTypes as $field => $format) {
            if($fieldName == $field) {
                return $format;
            }
        }
        return null;
    }

    /**
     * Este metodo se llama igual que en theframework con la diferencia que este transforma un valor de bd a bo
     * de modo que se muestre en un formato entendido por el usuario
     * @param string $sFieldName
     * @param string $sFieldValue
     * @return formated value
     */
    protected function getFormatedValue($fieldName, $fieldValue)
    {
        $value = "";
        $format = $this->getColformat($fieldName);
        switch($format) {
            case "date":
                $value = dbbo_date($fieldValue);
                break;

            case "datetime4":
                $value = dbbo_datetime4($fieldValue);
                break;

            case "datetime6":
                $value = dbbo_datetime6($fieldValue);
                break;

            case "time4":
                $value = dbbo_time4($fieldValue);
                break;

            case "time6":
                $value = dbbo_time6($fieldValue);
                break;

            case "int":
                $value = dbbo_int($fieldValue);
                break;

            case "numeric2":
                $value = dbbo_numeric2($fieldValue);
                break;

            default:
                $value = $fieldValue;
                break;
        }
        //bug($sValue,$sFieldName);
        return $value;
    }

    protected function buildNavigationButtons($htmlSelect)
    {
        $htmlUlButtons = "";
        $htmlUlButtons .= "<ul style=\"margin:0\">";
        if($this->infoCurrentPage > 1) {
            $htmlUlButtons .= "<li><a href=\"javascript:nav_click($this->infoFirstPage);\">&nbsp;&nbsp;<span class=\"awe-arrow-left\"></span>&nbsp;&nbsp;</a></li>";
            $htmlUlButtons .= "<li><a href=\"javascript:nav_click($this->infoPreviousPage);\">&nbsp;&nbsp;«&nbsp;&nbsp;</a></li>";
        }
        $htmlUlButtons .= "<li>&nbsp;Total: $this->infoNumRegs - ($this->infoCurrentPage/$this->infoNumPages)&nbsp;</li>";
        if($this->infoCurrentPage < $this->infoLastPage) {
            $htmlUlButtons .= "<li><a href=\"javascript:nav_click($this->infoNextPage);\">&nbsp;&nbsp;»&nbsp;&nbsp;</a></li>";
            $htmlUlButtons .= "<li><a href=\"javascript:nav_click($this->infoLastPage);\">&nbsp;&nbsp;<span class=\"awe-arrow-right\"></span>&nbsp;&nbsp;</a></li>";
        }

        if($this->infoNumPages > 1) {
            $htmlUlButtons .= "<li>&nbsp;&nbsp;Go to:&nbsp;&nbsp;$htmlSelect</li>";
        }
        $htmlUlButtons .= "</ul>";
        return $htmlUlButtons;
    }

    private function buildUrlNomethod($isByMvc = 0)
    {
        $urlNoMethod = [];
        if($this->isPermaLink) {
            //bugg();
            if(isset($_GET["tfw_iso_language"])) {
                $urlNoMethod[0] = $_GET["tfw_iso_language"];
            }

            if($isByMvc) {
                if(isset($_GET["tfw_package"])) {
                    $urlNoMethod[1] = $_GET["tfw_package"];
                }
                if(isset($_GET["tfw_controller"])) {
                    $urlNoMethod[2] = $_GET["tfw_controller"];
                }
                if(isset($_GET["tfw_partial"])) {
                    $urlNoMethod[3] = $_GET["tfw_partial"];
                }
            }
            //isByMvc=0
            else {
                if(isset($_GET["tfw_group"])) {
                    $urlNoMethod[1] = $_GET["tfw_group"];
                }
                if(isset($_GET["tfw_module"])) {
                    $urlNoMethod[2] = $_GET["tfw_module"];
                }
                if(isset($_GET["tfw_section"])) {
                    $urlNoMethod[3] = $_GET["tfw_section"];
                }
            }
            return implode("/", $urlNoMethod);
        }
        //no permalink
        else {
            if(isset($_GET["tfw_iso_language"])) {
                $urlNoMethod[1] = "lang=".$_GET["tfw_iso_language"];
            }

            if($isByMvc) {
                if($_GET["tfw_package"]) {
                    $urlNoMethod[1] = "package=".$_GET["tfw_package"];
                }
                if($_GET["tfw_controller"]) {
                    $urlNoMethod[2] = "controller=".$_GET["tfw_controller"];
                }
                if($_GET["tfw_partial"]) {
                    $urlNoMethod[3] = "partial=".$_GET["tfw_partial"];
                }
            }
            //isByMvc=0
            else {
                if($_GET["tfw_group"]) {
                    $urlNoMethod[1] = "group=".$_GET["tfw_group"];
                }
                if($_GET["tfw_module"]) {
                    $urlNoMethod[2] = "module=".$_GET["tfw_module"];
                }
                if($_GET["tfw_section"]) {
                    $urlNoMethod[3] = "section=".$_GET["tfw_section"];
                }
            }
            return implode("&", $urlNoMethod);
        }
    }//build_url_nomethod

    private function buildUrlExtras()
    {
        $get = $_GET;
        //bugg();
        $remove = array("tfw_iso_language","tfw_group","tfw_module","tfw_section","tfw_view"
                                        ,"tfw_package","tfw_controller","tfw_partial","tfw_method"
                          ,"page","selPage");
        //limpio los get
        foreach($get as $param => $value) {
            if(in_array($param, $remove)) {
                unset($get[$param]);
            }
        }

        if($this->isPermaLink) {
            return implode("/", $get);
        }
        //no permalink
        else {
            $url = [];
            foreach($get as $param => $value) {
                $url[] = "$param=$value";
            }
            return implode("&", $url);
        }
    }//build_url_extras

    /**
     * @@paginateurl
     * @return string
     */
    private function getPaginateView()
    {
        $view = $_GET["tfw_view"];
        if(!$view) {
            $view = $_GET["tfw_method"];
        }
        if(!$view) {
            return "get_list";
        } else {
            return $view;
        }
    }

    /**
     * @@paginateurl
     * @param type $isByMvc
     */
    protected function loadGetUrls($isByMvc = 0)
    {
        $urlNoMethod = $this->buildUrlNomethod($isByMvc);
        //pr($sUrlNoMethod);
        $urlExtras = $this->buildUrlExtras($isByMvc);

        //las sUrl update,delete,quarantine se usan para los botones de acciones en los listados
        //los href:this->sUrl<action>
        if($this->isPermaLink) {
            $this->urlNoview = "/$urlNoMethod/%replaceview%/";
            $this->urlDelete = "/".$urlNoMethod."/delete/";
            $this->urlUpdate = "/".$urlNoMethod."/update/";
            $this->urlQuarantine = "/".$urlNoMethod."/quarantine/";
            $this->urlPaginate = "/".$urlNoMethod."/";

            //CONFLICTO ENTRE DOS TIPOS DE RUTAS:
            //(en|es)/adminpannel/orders/orderlines/:id_order_head:[0-9]+:id_order_head:/:page:^(\s*|\d+)$:page:/
            //para esta, puesto que es una vista listado omito en la url "get_list" pq se sobre entiende
            //adminpannel/products/multiassign/12/ -> para esta necesito get_paginate_view
            //la paginación comun no lleva vista, pero la paginación en pestaña no es común (puede y no llevar),
            //para la paginación en popup (multiassign y singleassign) tiene que llevar
            if(in_array($_GET["tfw_view"], ["multiassign","singleassign"])) {
                $this->urlPaginate .= "{$_GET["tfw_view"]}/";
            }

            if($urlExtras) {
                $this->urlNoview .= "$urlExtras/";
                $this->urlDelete .= "$urlExtras/";
                $this->urlUpdate .= "$urlExtras/";
                $this->urlQuarantine .= "$urlExtras/";
                $this->urlPaginate .= "$urlExtras/";
            }
        }
        //no permalink
        else {
            $this->urlNoview = "?$urlNoMethod&view=%replaceview%";
            $this->urlDelete = "?".$urlNoMethod."&view=delete";
            $this->urlUpdate = "?".$urlNoMethod."&view=update";
            $this->urlQuarantine = "?".$urlNoMethod."&view=quarantine";
            //la paginación puede trtatarse de 4 formas noview,get_list,singleassign y multiassign
            $this->urlPaginate = "?".$urlNoMethod;
            //segun $_GET[view|method]
            $this->urlPaginate .= "&view=".$this->getPaginateView();

            if($urlExtras) {
                $this->urlNoview .= "&$urlExtras";
                $this->urlDelete .= "&$urlExtras";
                $this->urlUpdate .= "&$urlExtras";
                $this->urlQuarantine .= "&$urlExtras";
                $this->urlPaginate .= "&$urlExtras";
            }
        }
        //bug($this->sUrlPaginate,"urlpaginate.load_get_urls");
    }//load_get_urls()

    /**
     * Si se está usando mergekeys devolvera pkeys ya que los controles check
     * se crearán con con ids pkeys_i, si no se usa mergekeys entonces se crean los
     * controles con nombre tipo key1key2key3_0, key1key2key3_1 ... key1key2key3_n
     * @return string
     */
    protected function buildPostkey()
    {
        $checkName = "";
        if($this->doMergePkeys) {
            $checkName = "pkeys";
        } else {
            foreach($this->keyFields as $fldKeyName) {
                $checkName .= $fldKeyName;
            }
        }
        return $checkName;
    }

    protected function lowerFieldnames(&$rows)
    {
        $lowered = [];
        if(is_array($rows)) {
            foreach($rows as $i => $row) {
                $tmpRow = [];
                foreach($row as $fieldName => $value) {
                    $fieldName = strtolower($fieldName);
                    $tmpRow[$fieldName] = $value;
                }
                $lowered[$i] = $tmpRow;
            }
        }
        $rows = $lowered;
    }

    protected function fixLength(&$row, $fieldName)
    {
        //check if exists in array $this->arColumnLength
        if($this->hasColumnLength($fieldName)) {
            $value = $row[$fieldName];
            $lenVal = strlen($value);
            $lenConf = $this->getColumnLength($fieldName);
            //bug("f:$sFieldName,conflen:$iLenConf,valen:$iLenVal");
            if($lenVal > $lenConf) {
                //$sValue = htmlentities($sValue);
                $value = substr($value, 0, $lenConf)."...";
                $row[$fieldName] = $value;
            }
        }
    }
    //**********************************
    //             SETS
    //**********************************
    protected function setTmpjs($values = null)
    {
        $this->tmpJs = [];
        if(is_array($values)) {
            $this->tmpJs = $values;
        } elseif($values) {
            $this->tmpJs[] = $values;
        }
    }

    protected function addTmpjs($jsString)
    {
        if($jsString !== null) {
            $this->tmpJs[] = $jsString;
        }
    }

    public function setColumnDetail($isOn = true)
    {
        $this->isToDetail = $isOn;
    }
    public function setColumnDelete($isOn = true)
    {
        $this->isDeleteSingle = $isOn;
    }
    public function setColumnQuarantine($isOn = true)
    {
        $this->isQuarantineSingle = $isOn;
    }
    public function setColumnPickmultiple($isOn = true)
    {
        $this->isPickMultiple = $isOn;
    }
    public function mergePks($isOn = true, $glue = ",")
    {
        $this->doMergePkeys = $isOn;
        $this->mergeGlue = $glue;
    }
    public function setColumnPicksingle($isOn = true)
    {
        $this->isPickSingle = $isOn;
    }
    public function setKeyfields($keyFields)
    {
        $this->keyFields = $keyFields;
    }
    public function setOrderby($fieldNames)
    {
        $this->orderBy = $fieldNames;
    }
    public function setOrderbyType($orderWay)
    {
        $this->orderWay = $orderWay;
    }
    public function setUrlDelete($url)
    {
        $this->urlDelete = $url;
    }
    public function setUrlQuarantine($url)
    {
        $this->urlQuarantine = $url;
    }
    public function setUrlUpdate($url)
    {
        $this->urlUpdate = $url;
    }
    /**
     * Sirve para rescribir la url a donde senviara el formulario.
     * Es útil en caso de no cumplir con el standard de direcciones de theframework
     * @param string $sUrl
     */
    public function setUrlPaginate($url)
    {
        $this->urlPaginate = $url;
    }
    public function setUrlPicksingle($url)
    {
        $this->urlPickSingle = $url;
    }

    /**
     * Asigna el módulo y aplica las urls
     * @param string $sModule Modulo que gestiona el listado
     * cuando se usa esto??? deprecated por inclusion de load_get_urls()
     * Esto me rompe la paginación en las pestañas foreign
     */
    public function setModule($module)
    {

    }//set_module

    public function setCurrentPage($numPage)
    {
        $this->infoCurrentPage = $numPage;
    }
    public function setNextPage($numPage)
    {
        $this->infoNextPage = $numPage;
    }
    public function setPreviousPage($numPage)
    {
        $this->infoPreviousPage = $numPage;
    }
    public function setTotalRegs($numRegs)
    {
        $this->infoNumRegs = $numRegs;
    }
    public function setTotalPages($numPages)
    {
        $this->infoNumPages = $numPages;
    }
    public function setFirstPage($numFirstPage)
    {
        $this->infoFirstPage = $numFirstPage;
    }
    public function setLastPage($numLastPage)
    {
        $this->infoLastPage = $numLastPage;
    }
    public function setFields($objFields)
    {
        $this->objFields = $objFields;
    }
    public function isOrdenable($isOn = true)
    {
        $this->isOrdenable = $isOn;
    }
    public function setCheckOnrowclick($isOn = true)
    {
        $this->isCheckOnRowclick = $isOn;
    }

    /**
     * Datos que se desean pasar al popup por la url.
     * @param array $arData = array("keys"=>array("fieldname"=>value...),"extras"=>array("key"=>value..));
     */
    public function setMultiassign($data)
    {
        $this->assignMulti = $data;
    }
    public function setSingleassign($data)
    {
        $this->assignSingle = $data;
    }

    public function setMultiadd($data)
    {
        $this->multiAdd = $data;
    }
    /**
     * Con estos varlores se crean los campos hidden (por fila) con claves y descripciones que se pasaran a los destfields
     * @param array $arData array("destkey"=>"","destdesc"=>"","colkeys"=>",","coldescs"=>",");
     */
    public function setSingleadd($data)
    {
        $this->singleAdd = $data;
    }
    /**
     * format: ("date","datetime4","datetime6","time4","time6","int","numeric2")
     * @param array $arFormat array("fieldname"=>"format"...)
     */
    public function setFormatColumns($format)
    {
        $this->configColTypes = $format;
    }

    /**
     * @param array $arData array(0=>array("position"=>1,"label"=>"xxx","type"=>"anchor","href"=>"localhost/","hrefparams"=>array("col1","col2",)))
     */
    public function addExtraColums($data)
    {
        $this->extraColumns = $data;
    }

    /**
     * @param array $arColumns array("fieldname1","fieldname2"..)
     */
    public function setColumnHidden($columns)
    {
        $this->hiddenColumns = $columns;
    }

    /**
     *
     * @param array $arColumns array("fieldname"=>"value","f.."=>"val2"..))
     */
    public function setExtraHidden($columns)
    {
        $this->extraHidden = $columns;
    }

    public function setItemsPerPage($itemsPerPage)
    {
        $this->itemsPerPage = $itemsPerPage;
    }

    /**
     * Disable pagination controls
     * @param type $isOn
     */
    public function setNoPaginatebar($isOn = false)
    {
        $this->isPaginateBar = $isOn;
    }

    public function setColumnLength($columnLength)
    {
        $this->columnLength = [];
        if($columnLength) {
            $this->columnLength = $columnLength;
        }
    }
    public function addColumnLength($fieldName, $length)
    {
        $this->columnLength[$fieldName] = $length;
    }
    //**********************************
    //             GETS
    //**********************************
    protected function getTmpjs($glue = "\n")
    {
        return implode($glue, $this->tmpJs);
    }
    protected function hasColumnLength($fieldName)
    {
        $fieldNames = array_keys($this->columnLength);
        return in_array($fieldName, $fieldNames);
    }
    protected function getColumnLength($fieldName)
    {
        return $this->columnLength[$fieldName];
    }

}
