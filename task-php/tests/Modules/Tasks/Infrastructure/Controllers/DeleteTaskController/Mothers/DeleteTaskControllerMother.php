<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\DeleteTaskController\Mothers;

use App\Modules\Tasks\Infrastructure\Controllers\DeleteTaskController;

final class DeleteTaskControllerMother
{
    public static function getControllerInstance(): DeleteTaskController
    {
        return new DeleteTaskController();
    }
}
