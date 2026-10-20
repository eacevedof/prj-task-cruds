<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\GetUserController\Mothers;

use App\Modules\Users\Infrastructure\Controllers\GetUserController;

final class GetUserControllerMother
{
    public static function getControllerInstance(): GetUserController
    {
        return new GetUserController();
    }
}
