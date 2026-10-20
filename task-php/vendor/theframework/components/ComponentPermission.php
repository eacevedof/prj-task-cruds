<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 2.0.0
 * @name ComponentPermission
 * @file ComponentPermission.php
 * @date 30-07-2016 08:30
 */
class ComponentPermission
{
    protected $permissions;//dbpermissions. Pueden ser de varios módulos
    protected $module;//módulo a comprobar en el array anterior
    protected $modulePermission; //modulo
    protected $types;


    public function __construct($permissions = array(), $module = null)
    {
        if (!$module && $_GET["tfw_module"]) {
            $module = $_GET["tfw_module"];
        }
        $this->module = $module;
        $this->permissions = $permissions;
        $this->types = array("is_shortcut","is_select_menu","is_insert_menu","is_insert","is_select"
                                ,"is_update","is_delete","is_pick","is_quarantine","is_read","is_excelexport","is_print"
                                ,"is_foreign");
        //bug($this->arPermissions,"ComponentPermission.arPermissions");die;
        $this->load();
    }

    public function load()
    {
        if (is_array($this->permissions)) {
            $keys = array_keys($this->permissions);
            //si es numerico quiere decir que es un conjunto de filas
            if (isset($keys[0]) && is_numeric($keys[0])) {
                foreach($this->permissions as $permission) {
                    if ($permission["module"] == $this->module) {
                        $this->modulePermission = $permission;
                    }
                }
            }
            //es una fila en version corta
            elseif (isset($this->permissions["module"]) &&
                    ($this->permissions["module"] == $this->module)) {
                $this->modulePermission = $this->permissions;
            }
        }
    }

    public function grantAll()
    {
        foreach($this->types as $type) {
            $this->modulePermission[$type] = 1;
        }
    }

    public function denyAll()
    {
        foreach($this->types as $type) {
            $this->modulePermission[$type] = 0;
        }
    }

    /**
     * @param string $sType "is_select_menu","is_insert_menu","is_insert","is_select","is_update","is_delete","is_pick","is_quarantine","is_read","is_excelexport","is_print";
     */
    public function grant($type)
    {
        $this->modulePermission[$type] = 0;
    }
    /**
     * @param string $sType "is_select_menu","is_insert_menu","is_insert","is_select","is_update","is_delete","is_pick","is_quarantine","is_read","is_excelexport","is_print";
     */
    public function deny($type)
    {
        $this->modulePermission[$type] = 1;
    }

    //=======================
    //        GETS
    //=======================
    public function isShortcut()
    {
        return $this->modulePermission["is_shorcut"];
    }
    public function isSelectMenu()
    {
        return $this->modulePermission["is_select_menu"];
    }
    public function isInsertMenu()
    {
        return $this->modulePermission["is_insert_menu"];
    }
    public function isInsert()
    {
        return $this->modulePermission["is_insert"];
    }
    public function isSelect()
    {
        return $this->modulePermission["is_select"];
    }
    public function isUpdate()
    {
        return $this->modulePermission["is_update"];
    }
    public function isDelete()
    {
        return $this->modulePermission["is_delete"];
    }
    public function isPick()
    {
        return $this->modulePermission["is_pick"];
    }
    public function isQuarantine()
    {
        return $this->modulePermission["is_quarantine"];
    }
    public function isRead()
    {
        return $this->modulePermission["is_read"];
    }
    public function isExcelexport()
    {
        return $this->modulePermission["is_excelexport"];
    }
    public function isPrint()
    {
        return $this->modulePermission["is_print"];
    }
    public function isForeign()
    {
        return $this->modulePermission["is_foreign"];
    }

    //NOT
    public function isNotShortcut()
    {
        return !$this->modulePermission["is_shorcut"];
    }
    public function isNotSelectMenu()
    {
        return !$this->modulePermission["is_select_menu"];
    }
    public function isNotInsertMenu()
    {
        return !$this->modulePermission["is_insert_menu"];
    }
    public function isNotInsert()
    {
        return !$this->modulePermission["is_insert"];
    }
    public function isNotSelect()
    {
        return !$this->modulePermission["is_select"];
    }
    public function isNotUpdate()
    {
        return !$this->modulePermission["is_update"];
    }
    public function isNotDelete()
    {
        return !$this->modulePermission["is_delete"];
    }
    public function isNotPick()
    {
        return !$this->modulePermission["is_pick"];
    }
    public function isNotQuarantine()
    {
        return !$this->modulePermission["is_quarantine"];
    }
    public function isNotRead()
    {
        return !$this->modulePermission["is_read"];
    }
    public function isNotExcelexport()
    {
        return !$this->modulePermission["is_excelexport"];
    }
    public function isNotPrint()
    {
        return !$this->modulePermission["is_print"];
    }
    public function isNotForeign()
    {
        return !$this->modulePermission["is_foreign"];
    }

    //=======================
    //        SETS
    //=======================
    public function setShortcut($isOn = 1)
    {
        $this->modulePermission["is_shorcut"] = $isOn;
    }
    public function setSelectMenu($isOn = 1)
    {
        $this->modulePermission["is_select_menu"] = $isOn;
    }
    public function setInsertMenu($isOn = 1)
    {
        $this->modulePermission["is_insert_menu"] = $isOn;
    }
    public function setInsert($isOn = 1)
    {
        $this->modulePermission["is_insert"] = $isOn;
    }
    public function setSelect($isOn = 1)
    {
        $this->modulePermission["is_select"] = $isOn;
    }
    public function setUpdate($isOn = 1)
    {
        $this->modulePermission["is_update"] = $isOn;
    }
    public function setDelete($isOn = 1)
    {
        $this->modulePermission["is_delete"] = $isOn;
    }
    public function setPick($isOn = 1)
    {
        $this->modulePermission["is_pick"] = $isOn;
    }
    public function setQuarantine($isOn = 1)
    {
        $this->modulePermission["is_quarantine"] = $isOn;
    }
    public function setRead($isOn = 1)
    {
        $this->modulePermission["is_read"] = $isOn;
    }
    public function setModule($module)
    {
        $this->module = $module;
    }
    public function setPermissions($permissions)
    {
        $this->permissions = $permissions;
    }
    public function setExcelexport($isOn = 1)
    {
        $this->modulePermission["is_excelexport"] = $isOn;
    }
    public function setPrint($isOn = 1)
    {
        $this->modulePermission["is_print"] = $isOn;
    }
    public function setForeign($isOn = 1)
    {
        $this->modulePermission["is_foreign"] = $isOn;
    }

}//ComponentPermission
