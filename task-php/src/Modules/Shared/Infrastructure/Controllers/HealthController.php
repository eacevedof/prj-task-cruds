<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Controllers;

use PDO;

use App\Modules\Shared\Domain\Enums\HealthKeyEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;

/**
 * GET /health
 */
final class HealthController extends AbstractRestController
{
    public function index(): HttpResponder
    {
        return $this->respondOk([
            HealthKeyEnum::APP => HealthKeyEnum::APP_NAME,
            HealthKeyEnum::PHP => PHP_VERSION,
            HealthKeyEnum::PDO_DRIVERS => PDO::getAvailableDrivers(),
        ]);
    }
}
