<?php

use App\Core\Boot\BootPathsEnum;
use App\Core\Boot\ConsoleMain;

date_default_timezone_set("UTC");

if (!is_file("../vendor/autoload.php"))
    exit("missing vendor/autoload");

include_once "../vendor/autoload.php";
appboot_loadenv();

include_once "../vendor/theframework/bootstrap.php";
include_once BootPathsEnum::PATH_SRC."/Core/config/event_bus.php";

try {
    ConsoleMain::getInstance($argv)->exec();
}
catch (Throwable $ex) {
    ConsoleMain::debug($ex);
    ConsoleMain::onError($ex);
}
