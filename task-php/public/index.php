<?php

use App\Core\Boot\BootPathsEnum;
use App\Core\Boot\IndexMain;

date_default_timezone_set("UTC");

if (!is_file(dirname(__DIR__)."/vendor/autoload.php"))
    exit("missing vendor/autoload");

include_once dirname(__DIR__)."/vendor/autoload.php";
appboot_loadenv();

include_once dirname(__DIR__)."/vendor/theframework/bootstrap.php";
include_once BootPathsEnum::PATH_SRC."/Core/config/event_bus.php";

try {
    IndexMain::getInstance()->exec();
}
catch (Throwable $ex) {
    IndexMain::debug($ex);
    IndexMain::onError($ex);
}
