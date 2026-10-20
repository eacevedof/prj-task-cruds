<?php

declare(strict_types=1);

namespace Tests\Core\Graphql\GraphqlController\Mothers;

use App\Core\Graphql\GraphqlController;

final class GraphqlControllerMother
{
    public static function getControllerInstance(): GraphqlController
    {
        return new GraphqlController();
    }
}
