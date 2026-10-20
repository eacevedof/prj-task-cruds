<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\CreateTaskController\Mothers;

use App\Modules\Tasks\Infrastructure\Controllers\CreateTaskController;

final class CreateTaskControllerMother
{
    public static function getControllerInstance(): CreateTaskController
    {
        return new CreateTaskController();
    }
}
