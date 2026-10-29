<?php

declare(strict_types=1);

namespace Tests\Modules\Graphql\Infrastructure\Controllers\GraphqlController\Mothers;

use App\Modules\Graphql\Infrastructure\Controllers\GraphqlController;

final class GraphqlControllerMother
{
    public static function getControllerInstance(): GraphqlController
    {
        return new GraphqlController();
    }
}
