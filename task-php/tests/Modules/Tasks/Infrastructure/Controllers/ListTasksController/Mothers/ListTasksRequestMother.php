<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\ListTasksController\Mothers;

use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;

final class ListTasksRequestMother
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @return array<string, string> argumentos de ListTasksController::index()
     */
    public function getRequestByTestCase(
        ListTasksTestCaseEnum $useCase
    ): array
    {
        return match ($useCase) {
            ListTasksTestCaseEnum::OK_AS_SUCCESS_BY_DEFAULT =>
                HttpRequestMock::getInstance()
                    ->getMockedInstance(),
        };
    }
}
