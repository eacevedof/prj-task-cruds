<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\ListTasksController\Mothers;

use App\Modules\Tasks\Infrastructure\Controllers\ListTasksController;

final class ListTasksControllerMother
{
    public static function getControllerInstance(): ListTasksController
    {
        return new ListTasksController();
    }
}
