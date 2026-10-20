<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\CreateUserController\Mothers;

use App\Modules\Users\Infrastructure\Controllers\CreateUserController;

final class CreateUserControllerMother
{
    public static function getControllerInstance(): CreateUserController
    {
        return new CreateUserController();
    }
}
