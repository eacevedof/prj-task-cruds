<?php
declare(strict_types=1);

namespace App\Modules\Shared\Domain\Enums;

/**
 * Respuesta de GET /health.
 */
abstract class HealthKeyEnum
{
    public const string APP = "app";
    public const string PHP = "php";
    public const string PDO_DRIVERS = "pdo_drivers";
    public const string APP_NAME = "task-php";
}
