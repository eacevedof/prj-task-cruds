<?php
//autoload.php 2.0.0

$pathRoot = dirname(__FILE__);
if(!defined("IS_DEBUG_ALLOWED")) {
    define("IS_DEBUG_ALLOWED", 1);
}

$pathInclude = get_include_path().PATH_SEPARATOR.$pathRoot;
set_include_path($pathInclude);

spl_autoload_register(function ($nameSpacePath) {
    if (!strstr($nameSpacePath, "TheFramework\\"))
        return;

    include("array_helpers.php");
    $explode = explode("\\", $nameSpacePath);
    $className = end($explode);

    if(isset($helpers[$className])) {
        $fileName = $helpers[$className].".php";
        if(stream_resolve_include_path($fileName)) {
            include_once($fileName);
        }
    }
});
