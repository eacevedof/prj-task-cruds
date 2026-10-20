<?php

declare(strict_types=1);

namespace Tests\Modules\Users\Infrastructure\Controllers\UpdateUserController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use Tests\AbstractTestCase;
use Tests\Modules\Users\Infrastructure\Controllers\UpdateUserController\Mothers\{UpdateUserControllerMother,
    UpdateUserRequestMother,
    UpdateUserTestCaseEnum};

final class UpdateUserControllerTest extends AbstractTestCase
{
    public function testOkAsSuccessByValidFields(): void
    {
        $routeArgs = UpdateUserRequestMother::getInstance()
            ->getRequestByTestCase(UpdateUserTestCaseEnum::OK_AS_SUCCESS_BY_VALID_FIELDS);

        $httpResponse = UpdateUserControllerMother::getControllerInstance()->update(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_OK, $httpResponse->getStatusCode());
    }

    public function testNokAsBadRequestByEmptyUserName(): void
    {
        $routeArgs = UpdateUserRequestMother::getInstance()
            ->getRequestByTestCase(UpdateUserTestCaseEnum::NOK_AS_BAD_REQUEST_BY_EMPTY_USER_NAME);

        $httpResponse = UpdateUserControllerMother::getControllerInstance()->update(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_BAD_REQUEST, $httpResponse->getStatusCode());
    }

    public function testNokAsConflictByEmailOfOtherUser(): void
    {
        $routeArgs = UpdateUserRequestMother::getInstance()
            ->getRequestByTestCase(UpdateUserTestCaseEnum::NOK_AS_CONFLICT_BY_EMAIL_OF_OTHER_USER);

        $httpResponse = UpdateUserControllerMother::getControllerInstance()->update(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_CONFLICT, $httpResponse->getStatusCode());
    }

    public function testNokAsNotFoundByOtherUserUuid(): void
    {
        $routeArgs = UpdateUserRequestMother::getInstance()
            ->getRequestByTestCase(UpdateUserTestCaseEnum::NOK_AS_NOT_FOUND_BY_OTHER_USER_UUID);

        $httpResponse = UpdateUserControllerMother::getControllerInstance()->update(...$routeArgs);

        $this->assertInstanceOf(HttpResponder::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $httpResponse->getStatusCode());
    }
}
