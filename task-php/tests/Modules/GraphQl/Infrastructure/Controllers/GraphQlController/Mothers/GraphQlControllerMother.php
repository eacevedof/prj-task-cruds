<?php

declare(strict_types=1);

namespace Tests\Modules\GraphQl\Infrastructure\Controllers\GraphQlController\Mothers;

use App\Modules\GraphQl\Infrastructure\Controllers\ExecuteGraphQlQueryController;

final class GraphQlControllerMother
{
    public static function getControllerInstance(): ExecuteGraphQlQueryController
    {
        return new ExecuteGraphQlQueryController();
    }
}
