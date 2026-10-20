<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\LogInUserController\Mothers;

use App\Modules\Users\Infrastructure\Controllers\LogInUserController;

final class LogInUserControllerMother
{
    public static function getControllerInstance(): LogInUserController
    {
        return new LogInUserController();
    }
}
