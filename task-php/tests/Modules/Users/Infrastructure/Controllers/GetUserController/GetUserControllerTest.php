<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\GetUserController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use Tests\AbstractTestCase;
use Tests\Modules\Users\Infrastructure\Controllers\GetUserController\Mothers\{
    GetUserTestCaseEnum,
    GetUserRequestMother,
    GetUserControllerMother
};

final class GetUserControllerTest extends AbstractTestCase
{
    public function testOkAsSuccessByExistingUserUuid(): void
    {
        $routeArgs = GetUserRequestMother::getInstance()
            ->getRequestByTestCase(GetUserTestCaseEnum::OK_AS_SUCCESS_BY_EXISTING_USER_UUID);

        $httpResponse = GetUserControllerMother::getControllerInstance()->show(...$routeArgs);

        $this->assertInstanceOf(HttpResponse::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_OK, $httpResponse->getStatusCode());
    }

    public function testNokAsNotFoundByFakeUserUuid(): void
    {
        $routeArgs = GetUserRequestMother::getInstance()
            ->getRequestByTestCase(GetUserTestCaseEnum::NOK_AS_NOT_FOUND_BY_FAKE_USER_UUID);

        $httpResponse = GetUserControllerMother::getControllerInstance()->show(...$routeArgs);

        $this->assertInstanceOf(HttpResponse::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $httpResponse->getStatusCode());
    }

}
