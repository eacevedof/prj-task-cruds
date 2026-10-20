<?php

use App\Core\Boot\BootPathsEnum;

date_default_timezone_set("UTC");

if (!is_file("../vendor/autoload.php"))
    exit("missing vendor/autoload");

include_once "../vendor/autoload.php";
appboot_loadenv();

include_once "../vendor/theframework/bootstrap.php";
include_once BootPathsEnum::PATH_SRC."/Core/config/event_bus.php";

// Front controller provisional: solo health-check hasta que exista el router/IndexMain del primer modulo.
header("Content-Type: application/json");
echo json_encode([
    "code" => 200,
    "status" => true,
    "errors" => [],
    "data" => [
        "app" => "task-php",
        "php" => PHP_VERSION,
        "pdo_drivers" => PDO::getAvailableDrivers(),
    ],
]);
