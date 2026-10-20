<?php
declare(strict_types=1);

namespace App\Core\Boot;

// src/Core/Boot -> src/Core -> src -> <project root>
define("PATH_ROOT", dirname(__DIR__, 3));

abstract class BootPathsEnum
{
    public const string PATH_ROOT = PATH_ROOT;
    public const string PATH_PUBLIC = PATH_ROOT . "/public";
    public const string PATH_VENDOR = PATH_ROOT . "/vendor";
    public const string PATH_SRC = PATH_ROOT . "/src";
    public const string PATH_DISK_CACHE = PATH_ROOT . "/storage/cache";
    public const string PATH_SRC_CONFIG = PATH_ROOT . "/src/Core/config";
    public const string PATH_LOCALE = PATH_ROOT . "/src/Core/locale";
    public const string PATH_LOGS = PATH_ROOT . "/storage/logs";
    public const string PATH_CONSOLE = PATH_ROOT . "/console";
    public const string PATH_MIGRATIONS = PATH_ROOT . "/database/migrations";

    private function __construct() {}
}
