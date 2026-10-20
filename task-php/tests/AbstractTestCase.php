<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;
use Tests\Modules\Shared\Infrastructure\Repositories\JsonResponseRepositoryMock;

abstract class AbstractTestCase extends TestCase
{
    /**
     * Los controllers leen el body de $_POST, la query de $_GET y cabeceras/ip de $_SERVER: cada test parte de cero.
     */
    protected function setUp(): void
    {
        $_GET = [];
        $_POST = [];
        foreach (HttpRequestMock::SERVER_KEYS as $serverKey) {
            unset($_SERVER[$serverKey]);
        }
    }

    protected function dResponseUc(HttpResponder $httpResponse, mixed $useCase): void
    {
        JsonResponseRepositoryMock::fromHttpResponse($httpResponse)->getDebug($useCase);
    }

    protected function dResponse(HttpResponder $httpResponse): void
    {
        JsonResponseRepositoryMock::fromHttpResponse($httpResponse)->getDebug();
    }
}
