<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\UpdateUserController\Mothers;

use App\Modules\Users\Infrastructure\Controllers\UpdateUserController;

final class UpdateUserControllerMother
{
    public static function getControllerInstance(): UpdateUserController
    {
        return new UpdateUserController();
    }
}
