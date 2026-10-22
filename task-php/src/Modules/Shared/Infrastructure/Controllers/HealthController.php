<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Controllers;

use PDO;

use App\Modules\Shared\Infrastructure\Http\HttpResponse;

/**
 * GET /health
 */
final class HealthController extends AbstractApiController
{
    public function index(): HttpResponse
    {
        return $this->respondOk([
            "app" => "task-php",
            "php" => PHP_VERSION,
            "pdo_drivers" => PDO::getAvailableDrivers(),
        ]);
    }
}
