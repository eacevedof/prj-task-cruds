<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\ListTasksController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use Tests\AbstractTestCase;
use Tests\Modules\Tasks\Infrastructure\Controllers\ListTasksController\Mothers\{
    ListTasksTestCaseEnum,
    ListTasksRequestMother,
    ListTasksControllerMother
};

final class ListTasksControllerTest extends AbstractTestCase
{
    public function testOkAsSuccessByDefault(): void
    {
        $routeArgs = ListTasksRequestMother::getInstance()
            ->getRequestByTestCase(ListTasksTestCaseEnum::OK_AS_SUCCESS_BY_DEFAULT);

        $httpResponse = ListTasksControllerMother::getControllerInstance()->index(...$routeArgs);

        $this->assertInstanceOf(HttpResponse::class, $httpResponse);
        $this->assertEquals(ExceptionEnum::CODE_OK, $httpResponse->getStatusCode());
    }

}
