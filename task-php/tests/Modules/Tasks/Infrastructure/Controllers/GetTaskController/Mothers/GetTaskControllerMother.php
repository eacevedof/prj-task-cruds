<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\GetTaskController\Mothers;

use App\Modules\Tasks\Infrastructure\Controllers\GetTaskController;

final class GetTaskControllerMother
{
    public static function getControllerInstance(): GetTaskController
    {
        return new GetTaskController();
    }
}
