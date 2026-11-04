<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\SearchTasksByUserGraphQlController\Mothers;

use App\Modules\Tasks\Infrastructure\Controllers\SearchTasksByUserGraphQlController;

final class SearchTasksByUserGraphQlControllerMother
{
    public static function getControllerInstance(): SearchTasksByUserGraphQlController
    {
        return SearchTasksByUserGraphQlController::getInstance();
    }
}
