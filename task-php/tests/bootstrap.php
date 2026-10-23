<?php

declare(strict_types=1);

/**
 * Arranque de phpunit: mismo boot que public/index.php pero contra una BD SQLite PROPIA, que se
 * borra y se recrea (migraciones + fixtures) en cada ejecucion. La BD de desarrollo no se toca.
 */

use App\Core\Boot\BootPathsEnum;

use Tests\Modules\Shared\Infrastructure\Repositories\FixturesRawRepositoryMock;

require_once __DIR__."/../vendor/autoload.php";

// la variable REAL gana sobre el .env (appboot_loadenvfile), asi que esta ruta manda
$pathSqliteTest = BootPathsEnum::PATH_ROOT."/database/task_cruds_test.sqlite";
foreach (["APP_SQLITE_PATH" => $pathSqliteTest, "APP_ENV" => "test"] as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

appboot_loadenv();
include_once BootPathsEnum::PATH_VENDOR."/theframework/bootstrap.php";
include_once BootPathsEnum::PATH_SRC."/Core/config/event_bus.php";

if (is_file($pathSqliteTest)) {
    unlink($pathSqliteTest);
}
FixturesRawRepositoryMock::getInstance()->createDbWithFixtures();
