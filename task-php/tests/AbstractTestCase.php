<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;

use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use Tests\Modules\Shared\Infrastructure\Repositories\JsonResponseRepositoryMock;

abstract class AbstractTestCase extends TestCase
{
    /**
     * Los controllers leen el body de $_POST y la query de $_GET: cada test parte de cero.
     */
    protected function setUp(): void
    {
        $_GET = [];
        $_POST = [];
    }

    protected function dResponseUc(HttpResponse $httpResponse, mixed $useCase): void
    {
        JsonResponseRepositoryMock::fromHttpResponse($httpResponse)->getDebug($useCase);
    }

    protected function dResponse(HttpResponse $httpResponse): void
    {
        JsonResponseRepositoryMock::fromHttpResponse($httpResponse)->getDebug();
    }
}
