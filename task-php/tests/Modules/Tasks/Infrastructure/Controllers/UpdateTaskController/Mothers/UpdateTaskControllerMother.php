<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\UpdateTaskController\Mothers;

use App\Modules\Tasks\Infrastructure\Controllers\UpdateTaskController;

final class UpdateTaskControllerMother
{
    public static function getControllerInstance(): UpdateTaskController
    {
        return new UpdateTaskController();
    }
}
