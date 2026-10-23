<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\DeleteUserController\Mothers;

use App\Modules\Users\Infrastructure\Controllers\DeleteUserController;

final class DeleteUserControllerMother
{
    public static function getControllerInstance(): DeleteUserController
    {
        return new DeleteUserController();
    }
}
