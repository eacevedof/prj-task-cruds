<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\RegenerateUserApiTokenController\Mothers;

use App\Modules\Users\Infrastructure\Controllers\RegenerateUserApiTokenController;

final class RegenerateUserApiTokenControllerMother
{
    public static function getControllerInstance(): RegenerateUserApiTokenController
    {
        return new RegenerateUserApiTokenController();
    }
}
