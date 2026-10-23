<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\ListUsersController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use Tests\AbstractTestCase;
use Tests\Modules\Users\Infrastructure\Controllers\ListUsersController\Mothers\{
    ListUsersTestCaseEnum,
    ListUsersRequestMother,
    ListUsersControllerMother
};

final class ListUsersControllerTest extends AbstractTestCase
{
    public function testOkAsSuccessByDefault(): void
    {
        $routeArgs = ListUsersRequestMother::getInstance()
            ->getRequestByTestCase(ListUsersTestCaseEnum::OK_AS_SUCCESS_BY_DEFAULT);

        $httpResponse = ListUsersControllerMother::getControllerInstance()->index(...$routeArgs);

        $this->assertInstanceOf(HttpResponse::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_OK, $httpResponse->getStatusCode());
    }

}
