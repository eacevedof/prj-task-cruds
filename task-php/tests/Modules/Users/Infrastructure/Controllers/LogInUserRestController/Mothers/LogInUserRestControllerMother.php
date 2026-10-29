<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\LogInUserRestController\Mothers;

use App\Modules\Users\Infrastructure\Controllers\LogInUserRestController;

final class LogInUserRestControllerMother
{
    public static function getControllerInstance(): LogInUserRestController
    {
        return new LogInUserRestController();
    }
}
