<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.8.1
 * @name HelperJavascript
 * @file HelperJavascript.php
 * @date 06-02-2017 13:59 (SPAIN)
 * @observations core library
 */

namespace TheFramework\Helpers;

use TheFramework\Helpers\TheFrameworkHelper;

class HelperJavascript extends TheFrameworkHelper
{
    protected $subpathPlugin;
    protected $subpathJs;
    protected $subpathExt;
    protected $saveAction;
    protected $focusId;
    protected $filters;
    protected $formId;

    protected $pathfiles = array("type" => "custom|jquery","filename");
    protected $jsLines = array();

    protected $validate = array();
    protected $tmpJs;
    protected $multiForm = [];

    public function __construct($subPathExt = "", $subPathFiles = array())
    {
        $this->pathfiles = $subPathFiles;
        if(defined("TFW_SUBPATH_JSDS")) {
            $this->subpathJs = TFW_SUBPATH_JSDS;
        }//@check
        if(defined("TFW_SUBPATH_PLUGINDS")) {
            $this->subpathPlugin = TFW_SUBPATH_PLUGINDS;
        }//@check
        $this->subpathExt = $subPathExt;
        $this->formId = "frmList";
        //esto se escribe en hidAction y despues se comprueba con ->is_updating o ->is_inserting
        $this->saveAction = "insert";//insertlist,update,updatelist
        $this->focusId = "frmList";
    }

    private function addFilesrc($fileName, $type, $path)
    {
        if(!strpos($fileName, ".js")) {
            $fileName .= ".js";
        }
        if(!empty($type)) {
            $path .= "$type/";
        }
        $path .= $fileName;
        if(!in_array($path, $this->pathfiles)) {
            $this->pathfiles[] = $path;
        }
    }

    private function htmlLinesBetweenTags()
    {
        $lines = "";
        foreach($this->jsLines as $line) {
            $lines .= $line ."\n";
        }
        return $lines;
    }

    /**
     * Devuelve inner_html si existiera unido a las lineas añadidas
     * al array de lineas con add_js_line() formando todos es to "any content"
     * @return string tipo <script..>...any content ...</script>
     */
    public function getHtml()
    {
        $js = $this->getOpentag();
        if($this->_inner_html) {
            $js .= $this->_inner_html;
        }
        $js .= $this->htmlLinesBetweenTags();
        $js .= $this->getClosetag();
        return $js;
    }

    public function showTagLinks()
    {
        echo $this->getHtmlTagLinks();
    }

    public function addMultiform($formId, $saveAction = "insert")
    {
        $this->multiForm[] = ["formid" => $formId,"saveaction" => $saveAction];
    }

    //**********************************
    //             SETS
    //**********************************
    public function addTfwFilesrc($fileName, $type = "custom")
    {
        $this->addFilesrc($fileName, $type, $this->subpathJs);
    }
    //public function set_path_files($arPaths){$this->arPathfiles = $arPaths;}
    public function addJsLine($scriptLine)
    {
        $this->jsLines[] = $scriptLine;
    }
    public function addPathFile($filePath)
    {
        $this->pathfiles[] = $filePath;
    }
    public function addPlugFilesrc($folderName, $fileName)
    {
        $path = $this->subpathPlugin.$folderName."/js/";
        $this->addFilesrc($fileName, null, $path);
    }

    public function addExtFilesrc($fileName, $subPath = "")
    {
        if(empty($this->subpathExt)) {
            if(empty($subPath)) {
                $subPath = "js/";
            } else {
                $subPath = $this->subpathExt;
            }
        }
        $this->addFilesrc($fileName, null, $subPath);
    }

    public function setValidateConfig($validate)
    {
        $this->validate = $validate;
    }
    public function setUpdateaction($action = "update")
    {
        $this->saveAction = $action;
    }

    //**********************************
    //             GETS
    //**********************************
    public function getOpentag($attrib = "")
    {
        return "<script type=\"text/javascript\"$attrib>\n";
    }
    public function getClosetag()
    {
        return "</script>\n";
    }

    /**
     * devuelve un tag script con atributo src
     * @param string $sSrcPath
     * @return string <script type=\"text/javascript\" src=\"$sSrcPath\"></script>
     */
    private function getHtmlTagScriptLink($srcPath)
    {
        $scriptTag = "";
        if($srcPath) {
            $scriptTag = "<script type=\"text/javascript\" src=\"$srcPath\"></script>\n";
        }
        return $scriptTag;
    }

    /**
     * Para poder cargar estos scripts se debe de añadir las rutas con addfile
     * Devuelve varios tags de tipo script src
     * @return string de tipo <script src=...></script> <script ...></script>
     */
    public function getHtmlTagLinks()
    {
        $links = "";
        foreach($this->pathfiles as $srcPath) {
            $links .= $this->getHtmlTagScriptLink($srcPath);
        }
        return $links;
    }

    public function save()
    {
        $js = $this->getOpentag(" helper=\"javascript.fn_save\"");
        $js .= "
        //sMessage: Confirm message
        //sUrl: send form url
        function $this->saveAction(sMessage,sUrl)
        {
            var sFormId = \"$this->formId\";
            var oForm = document.getElementById(sFormId);
            if(oForm)
            {
                var objError = {};
                objError.message = \"\";
                
                if(window.arObjFields!==undefined)
                    if(TfwFieldValidate && arObjFields!=null)
                        objError = TfwFieldValidate.checkfields(arObjFields);

                if(objError.message==\"\")
                {
                    var isConfirmed = true;
                    if(sMessage)
                        isConfirmed = confirm(sMessage);
                    
                    if(isConfirmed)
                    {
                        var oHidAction = document.getElementById(\"hidAction\");
                        if(oHidAction)oHidAction.value=\"$this->saveAction\";
                        if(sUrl) oForm.action = sUrl;
                        oForm.submit();
                        return true;
                    }
                }
                else
                {
                    alert(objError.message);
                }
            }
            return false;
        }
        ";
        $js .= $this->getClosetag();
        return $js;
    }

    public function parentForm()
    {
        $js = $this->getOpentag(" helper=\"javascript.fn_parentform\"");
        $js .= "
        var get_parentform = function(oElem)
        { 
            if(oElem.form!==undefined)
                return oElem.form;
            return null;
        };
        ";
        $js .= $this->getClosetag();
        return $js;
    }

    public function setValLike()
    {
        $js = $this->getOpentag(" helper=\"javascript.fn_setvallike\"");
        $js .= "
        var set_vallike = function(oForm,sIdLike,sValue)
        { 
            var arElem = oForm.elements;
            if(arElem)
            {
                var oTmp = null;
                for(var i=0; i<arElem.length; i++)
                {
                    oTmp = arElem[i];
                    if(oTmp.id != undefined)
                    {
                        var sId = oTmp.id;
                        if(sId.indexOf(sIdLike)!=-1)
                        {
                            oTmp.value = sValue;
                        }
                    }
                }
            }//if arElem
        };
        ";
        $js .= $this->getClosetag();
        return $js;
    }

    public function saveMulti()
    {
        $js = $this->getOpentag(" helper=\"javascript.fn_savemulti\"");
        $actions = [];
        foreach($this->multiForm as $config) {
            $actions[] = $config["saveaction"];
        }

        $actions = array_unique($actions);
        foreach($actions as $action) {
            $js .= "
            function {$action}multi(eClicked,sMessage,sUrl)
            {
                var oForm = get_parentform(eClicked);

                if(oForm)
                {
                    var objError = {};
                    objError.message = \"\";

                    if(window.arObjFields!==undefined)
                        if(TfwFieldValidate && arObjFields!=null)
                            objError = TfwFieldValidate.checkfieldsform(arObjFields,oForm);

                    if(objError.message==\"\")
                    {
                        var isConfirmed = true;
                        if(sMessage)
                            isConfirmed = confirm(sMessage);

                        if(isConfirmed)
                        {
                            set_vallike(oForm,\"hidAction\",\"$action\");

                            var sIdName = \"\";
                            if(oForm.id != undefined) sIdName=oForm.id;
                            if(sIdName==\"\" && oForm.name!=undefined) sIdName=oForm.name;
                            set_vallike(oForm,\"hidForm\",sIdName);

                            if(sUrl)oForm.action = sUrl;
                            oForm.submit();
                            return true;
                        }
                    }
                    else
                    {
                        alert(objError.message);
                    }
                }
                return false;
            }//{$action}multi
            ";
        }
        $js .= $this->getClosetag();
        return $js;
    }

    public function checkBeforeSave($validate = array())
    {
        if(!$validate) {
            $validate = $this->validate;
        }
        //bug($arValidate,"check_before_save");
        //$arFieldsConfig["first_name"] = array("label"=>tr_first_name,"length"=>100,"type"=>array("required"),"id"=>"");
        //var oTmpField = null;//TfwField(sId,iLen,sLabel,arValidate)
        //consturir esto: arObjFields
        //TfwFieldValidate.checkfields($arFieldsConfig);
        $jsObjs = array();
        $jsObjs[] = "var arObjFields = [];";
        if($this->multiForm) {
            foreach($this->multiForm as $config) {
                $formId = $config["formid"];
                if(isset($validate[$formId])) {
                    $rules = $validate[$formId];
                }
                //bug($arRules,"formid:$sFormId");
                foreach($rules as $data) {
                    $controlId = "";
                    if(isset($data["id"])) {
                        $controlId = $data["id"];
                    }
                    if(!$controlId) {
                        $controlId = $data["controlid"];
                    }
                    $len = $data["length"];
                    $label = $data["label"];
                    //arObjFields.push(new TfwField('',100,'Last Name',[''required'']));
                    //si se ha definido una longitud se valida por este dato
                    if($len) {
                        $data["type"][] = "length";
                    }
                    $validates = $this->getStringArray($data["type"]);
                    $jsObj = "new TfwField('$controlId',$len,'$label',$validates,'$formId')";
                    $jsObjs[] = "arObjFields.push($jsObj);";
                }
            }
        } else {
            foreach($validate as $data) {
                //pr("no multi");
                $controlId = "";
                if(isset($data["id"])) {
                    $controlId = $data["id"];
                }
                if(!$controlId) {
                    $controlId = $data["controlid"];
                }
                $len = $data["length"];
                $label = $data["label"];
                //arObjFields.push(new TfwField('',100,'Last Name',[''required'']));
                //si se ha definido una longitud se valida por este dato
                $formId = "";
                if($len) {
                    $data["type"][] = "length";
                }
                $validates = $this->getStringArray($data["type"]);
                $jsObj = "new TfwField('$controlId',$len,'$label',$validates,'$formId')";
                $jsObjs[] = "arObjFields.push($jsObj);";
            }
        }

        $js = $this->getOpentag(" helper=\"javascript.check_before_save\"");
        $js .= implode("\n", $jsObjs);
        $js .= $this->getClosetag();
        $js .= $this->save();
        $js .= $this->parentForm();
        $js .= $this->setValLike();
        $js .= $this->saveMulti();
        return $js;
    }//check_before_save

    public function getStringArray($values)
    {
        $vals = array();
        $isArray = false;
        foreach($values as $value) {
            if(is_array($value)) {
                $value = $this->getStringArray($value);
                $isArray = true;
            } else {
                $value = str_replace("'", "\'", $value);
            }
            $vals[] = $value;
        }

        if($isArray) {
            return "[".implode(",", $vals)."]";
        }
        return "['".implode("','", $vals)."']";
    }

    protected function convertToUrlparams($forParams)
    {
        $url = array();
        foreach($forParams as $params) {
            foreach($params as $fieldName => $value) {
                $url[] = "$fieldName=$value";
            }
        }
        return implode("&", $url);
    }

    public function arrayToJson($array)
    {
        //bug($array,"array");
        //'{"result":true,"count":1}',
        $pairs = array();
        foreach($array as $key => $value) {
            if(is_array($value)) {
                $value = $this->arrayToJson($value);
                $pairs[] = "\"$key\":$value";
            } else {
                $pairs[] = "\"$key\":\"$value\"";
            }
        }
        $json = implode(",", $pairs);
        $json = "{".$json."}";
        //bug($sJson);
        return $json;
    }

    public function multiAssignWindow()
    {
        $this->setTmpjs();

        if($this->isPermaLink) {
            //@@TODO
            $this->addTmpjs("window.open(\"/\"+sUrlAction,\"multiassign\",\"width=\"+iW+\",height=\"+iH,status=0,scrollbars=1,resizable=0,left=0,top=0);");
        } else {
            $this->addTmpjs("window.open(sUrlAction,\"multiassign\",\"width=\"+iW+\",height=\"+iH,status=0,scrollbars=1,resizable=0,left=0,top=0);");
        }

        $tmpJs = $this->getTmpjs();

        $js = $this->getOpentag(" helper=\"javascript.fn_multiassign_window\"");
        $js .= "

        function multiassign_window(sUrlAction,iW,iH)
        {
            var iW = iW||800;
            var iH = iH||600;
            var sUrlAction = sUrlAction || window.location.search;
            $tmpJs
        }
        ";
        $js .= $this->getClosetag();
        return $js;
    }

    public function singleAssignWindow()
    {
        $this->setTmpjs();

        if($this->isPermaLink) {
            $this->addTmpjs("window.open(\"/\"+sUrlAction,\"multiassign\",\"width=\"+iW+\",height=\"+iH,status=0,scrollbars=0,resizable=0,left=0,top=0);");
        } else {
            $this->addTmpjs("window.open(sUrlAction,\"multiassign\",\"width=\"+iW+\",height=\"+iH,status=0,scrollbars=0,resizable=0,left=0,top=0);");
        }

        $tmpJs = $this->getTmpjs();

        $js = $this->getOpentag(" helper=\"javascript.fn_singleassign_window\"");
        $js .= "
        function singleassign_window(sUrlAction,iW,iH)
        {
            var iW = iW||800;
            var iH = iH||600;
            var sUrlAction = sUrlAction || window.location.search;
            $tmpJs
        }
        ";
        $js .= $this->getClosetag();
        return $js;
    }

    public function singleAdd()
    {
        $js = $this->getOpentag(" helper=\"javascript.fn_singleadd\"");
        $js .= "
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
        $js .= $this->getClosetag();
        return $js;
    }

    protected function buildJsFieldIds()
    {
        $ids = array_values($this->filters);
        $fieldIds = implode("\",\"", $ids);
        $fieldIds = "\"$fieldIds\"";
        return $fieldIds;
    }

    public function postBack()
    {
        $js = $this->getOpentag(" helper=\"javascript.fn_postback\"");
        $js .= "
        function postback(oControl)
        {
            var oForm = oControl.form;
            if(oForm) 
            {
                var sIdName = \"\";
                if(oForm.id != undefined) sIdName=oForm.id;
                if(sIdName==\"\" && oForm.name!=undefined) sIdName=oForm.name;
                set_vallike(oForm,\"hidForm\",sIdName);
                set_vallike(oForm,\"hidAction\",\"postback\");
                set_vallike(oForm,\"hidPostback\",oControl.id);
                
                oForm.action = window.location;
                oForm.submit();
            }
            else
                bug(\"form not found - fn_postback\");
        }
        ";
        $js .= $this->getClosetag();
        return $js;
    }

    public function resetFilters()
    {
        $fieldIds = $this->buildJsFieldIds();
        $js = $this->getOpentag(" helper=\"javascript.fn_resetfilters\"");
        $js .= "
        function reset_filters()
        {
            var arFields = [$fieldIds];
            TfwFieldValidate.reset(arFields);
        }
        ";
        $js .= $this->getClosetag();
        return $js;
    }

    public function multiAdd()
    {
        $this->setTmpjs();

        $js = $this->getOpentag(" helper=\"javascript.fn_multiadd\"");
        $js .= "
        function multiadd(sUrlAction,sIdForm)
        {
            var sIdForm = sIdForm || \"$this->formId\"; 
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
        $js .= $this->getClosetag();
        return $js;
    }

    public function onEnterInsert()
    {
        $js = $this->getOpentag(" helper=\"javascript.fn_onenter_insert\"");
        $js .= "
        function onenter_insert(oEvent)
        {
            var iKeyPressed = 0;
            //var event = this.event;
            if(window.event) iKeyPressed = window.event.keyCode;
            else if(oEvent) iKeyPressed = oEvent.which;
            else return true;

            if(iKeyPressed == 13)
            {
                insert(); 
                return false;
            }
            else
               return true;
        }
        ";
        $js .= $this->getClosetag();
        return $js;
    }

    public function onEnterUpdate()
    {
        $js = $this->getOpentag(" helper=\"javascript.fn_onenter_update\"");
        $js .= "
        function onenter_update(oEvent)
        {
            var iKeyPressed = 0;
            //var event = this.event;
            if(window.event) iKeyPressed = window.event.keyCode;
            else if(oEvent) iKeyPressed = oEvent.which;
            else return true;

            if(iKeyPressed == 13)
            {
                update(); 
                return false;
            }
            else
               return true;
        }
        ";
        $js .= $this->getClosetag();
        return $js;
    }

    public function onEnterSubmit()
    {
        $js = $this->getOpentag(" helper=\"javascript.fn_onenter_submit\"");
        $js .= "
        function onenter_submit(oEvent)
        {
            var sFormId = \"$this->formId\";
            var iKeyPressed = 0;
            //var event = this.event;
            if(window.event) iKeyPressed = window.event.keyCode;
            else if(oEvent) iKeyPressed = oEvent.which;
            else return TRUE;

            if(iKeyPressed == 13)
            {
                var sUrl = window.location;
                var oForm = document.getElementById(sFormId);
                var oHidAction = document.getElementById('hidAction');
                if(oForm) 
                {
                    if(oHidAction) oHidAction.value = 'enter_submit';
                    oForm.action = sUrl;
                    oForm.submit();
                }
                else
                    bug('form id not defined - fn_onenter_submit');
             }
        }
        ";
        $js .= $this->getClosetag();
        return $js;
    }

    public function closeMe()
    {
        $js = $this->getOpentag(" helper=\"javascript.fn_closeme\"");
        $js .= "function closeme(){self.close();}";
        $js .= $this->getClosetag();
        return $js;
    }

    public function scriptBotchecker()
    {
        ?>
<script src="/js/the_framework/tfw/helpers/helper_form.js" helper="javascript.script_botchecker" type="text/javascript"></script>
<script src="/js/the_framework/tfw/helpers/helper_input_hidden.js" helper="javascript.script_botchecker" type="text/javascript"></script>
<script src="/js/the_framework/tfw/helpers/helper_input_text.js" helper="javascript.script_botchecker" type="text/javascript"></script>
<script src="/js/the_framework/tfw/helpers/helper_label.js" helper="javascript.script_botchecker" type="text/javascript"></script>
<script src="/js/the_framework/classes/js_botchecker.js" helper="javascript.script_botchecker" type="text/javascript"></script>
<script type="text/javascript" helper="javascript.script_botchecker">
    var eCount = document.getElementById("spAfter");
    var oForm = new HelperForm("frmComment");
    
    oInput = new HelperInputText("txtBot");
    oInput.set_type("number");
    //oInput.set_value("soy botero");
    oInput = oInput.get_element();
    oForm.add_field(oInput,eCount);
    
    oInput = new HelperLabel("lblBot","txtBot");
    oInput.add_attribute("class","control-label");
    oInput.set_innerhtml("-");
    oInput = oInput.get_element();
    oForm.add_field(oInput,eCount);
    
    var oInput = new HelperInputHidden("hidBot");
    oInput.set_value(":)");
    oInput = oInput.get_element();
 
    var oBot = new Botchecker();
    oBot.set_label("lblBot");
    oBot.set_txt("txtBot");
    oBot.set_hidden("hidBot");
    oBot.randomize();
    
    var fnOnsubmit = function()
    {
        var isEmail = function(sEmail)
        {
            var sPattern = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return sPattern.test(sEmail);
        }

        var oTxt = document.getElementById("txtEmail");
        if(!isEmail(oTxt.value))
        {
            var sMessage = get_valmessage("valemail","fieldname","Email");
            alert(sMessage);
            oTxt.focus();
            return false;
        }
        
        var oError = TfwFieldValidate.checkfields(arObjFields);
        if(oError.message==="")
        {
            if(oBot.check())
            {
                oTxt = document.getElementById("hidAction");
                oTxt.value = "insert";
                return true;
            }
            else
            {
                oTxt = document.getElementById("txtBot");
                oTxt.value="";
                oTxt.focus();
            }
        }
        else
            alert(oError.message);
        return false;
    }//fnOnsubmit
    
    oForm.on_submit(fnOnsubmit);
</script>
<?php
    }//script_botchecker

    public function showTr()
    {
        $lang = $this->get_get("tfw_iso_language");
        $pathTr = TFW_PATH_FOL_THEAPPLICATIONDS."translations/$lang/translate_validate.php";
        $tr = file_get_contents($pathTr);
        $tr = trim($tr);
        $fdef = strpos($tr, "define(");
        $tr = substr($tr, $fdef);
        $tr = str_replace("define(\"tr_js_", "", $tr);
        //$sTr = trim($sTr);
        $lines = explode("\");", $tr);
        $trs = [];
        foreach($lines as $line) {
            $tmps = explode("\",\"", $line);
            if($tmps[0]) {
                //bug($arTmp);
                $trs[trim($tmps[0])] = trim($tmps[1]);
            }
        }
        //bug($arTr);
        if($trs) {
            $js = [];
            $js[] = $this->getOpentag(" helper=\"javascript.show_tr\"");
            $js[] = "var oTfwtr = {};";
            foreach($trs as $tr => $str) {
                $tmp = "oTfwtr.$tr = \"$str\";";
                $js[] = $tmp;
            }
            $js[] = "function get_valmessage(sType,sTag,sRep)
            {
                var sMessage = \"\";
                if(oTfwtr[sType]!==undefined)
                {
                    sMessage = oTfwtr[sType];
                    if(sTag && sRep)
                        sMessage = sMessage.replace(\"%%\"+sTag+\"%%\",sRep);
                }
                return sMessage;
            }";
            //$arJs[] = "console.log(oJstr);alert(oJstr.vallength);";
            $js[] = $this->getClosetag();
            s(implode("\n", $js));
        }
    }//show_tr

    public function setFocusid($value)
    {
        $this->focusId = $value;
    }
    public function setFilters($fields)
    {
        $this->filters = $fields;
    }
    public function setFormid($value)
    {
        $this->formId = $value;
    }

    public function processFocus()
    {
        $js = $this->getOpentag(" helper=\"javascript.process_focus\"");
        $js .=
        "   var oElement = document.getElementById(\"$this->focusId\");
            if(!oElement) oElement = document.getElementsByName(\"$this->focusId\")[0];
            if(oElement && (oElement.focus!=undefined)) oElement.focus();
        ";
        $js .= $this->getClosetag();
        return $js;
    }

    protected function setTmpjs($values = null)
    {
        $this->tmpJs = array();
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

    protected function getTmpjs($glue = "\n")
    {
        return implode($glue, $this->tmpJs);
    }
    //**********************************
    //           MAKE PUBLIC
    //**********************************
    public function showOpentag()
    {
        echo $this->getOpentag();
    }
    public function showClosetag()
    {
        echo $this->getClosetag();
    }
    public function showMultiAssign()
    {
        echo $this->multiAssignWindow();
    }
    public function showSingleAssignWindow()
    {
        echo $this->singleAssignWindow();
    }
    public function showMultiAdd()
    {
        echo $this->multiAdd();
    }
    public function showSingleAdd()
    {
        echo $this->singleAdd();
    }
    public function showCloseMe()
    {
        echo $this->closeMe();
    }
    public function showResetFilters()
    {
        echo $this->resetFilters();
    }
    public function showSetFocus()
    {
        echo $this->processFocus();
    }
    public function showSave()
    {
        echo $this->save();
    }
    public function showCheckBeforeSave($validate = array())
    {
        echo $this->checkBeforeSave($validate);
    }
    public function showPostBack()
    {
        echo $this->postBack();
    }
    public function showParentForm()
    {
        echo $this->parentForm();
    }
    public function showSetValLike()
    {
        echo $this->setValLike();
    }
    public function showEnterInsert()
    {
        echo $this->onEnterInsert();
    }
    public function showEnterUpdate()
    {
        echo $this->onEnterUpdate();
    }
    public function showEnterSubmit()
    {
        echo $this->onEnterSubmit();
    }

}//HelperJavascript
