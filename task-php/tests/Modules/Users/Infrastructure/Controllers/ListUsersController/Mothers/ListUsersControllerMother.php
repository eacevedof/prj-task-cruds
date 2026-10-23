<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\ListUsersController\Mothers;

use App\Modules\Users\Infrastructure\Controllers\ListUsersController;

final class ListUsersControllerMother
{
    public static function getControllerInstance(): ListUsersController
    {
        return new ListUsersController();
    }
}
