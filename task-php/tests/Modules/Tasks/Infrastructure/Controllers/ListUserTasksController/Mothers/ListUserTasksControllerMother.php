<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\ListUserTasksController\Mothers;

use App\Modules\Tasks\Infrastructure\Controllers\ListUserTasksController;

final class ListUserTasksControllerMother
{
    public static function getControllerInstance(): ListUserTasksController
    {
        return new ListUserTasksController();
    }
}
