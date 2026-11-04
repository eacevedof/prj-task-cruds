<?php

declare(strict_types=1);

namespace Tests\Modules\Tasks\Infrastructure\Controllers\SearchTasksByUserGraphQlController;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\GraphQl\Domain\Exceptions\GraphQlException;
use Tests\AbstractTestCase;
use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;
use Tests\Modules\Shared\Infrastructure\Repositories\TasksRawRepositoryMock;
use Tests\Modules\Shared\Infrastructure\Repositories\UsersRawRepositoryMock;
use Tests\Modules\Tasks\Infrastructure\Controllers\SearchTasksByUserGraphQlController\Mothers\SearchTasksByUserGraphQlControllerMother;

/**
 * Resolver de User.tasks probado directamente: por /graphql solo se llega con el User del propio token, asi que el
 * 404 de un usuario ajeno (lo que cubria la query userTasks, retirada en 3.0.0) solo se puede probar aqui.
 */
final class SearchTasksByUserGraphQlControllerTest extends AbstractTestCase
{
    public function testOkAsSuccessByOwnUserUuid(): void
    {
        $this->setAuthenticatedRequest();

        $taskPage = SearchTasksByUserGraphQlControllerMother::getControllerInstance()->invoke(
            UsersRawRepositoryMock::USER_UUID_OK,
            []
        );

        $this->assertContains(TasksRawRepositoryMock::TASK_UUID_OK, array_column($taskPage["items"], "task_uuid"));
    }

    public function testNokAsNotFoundByOtherUserUuid(): void
    {
        $this->setAuthenticatedRequest();

        try {
            SearchTasksByUserGraphQlControllerMother::getControllerInstance()->invoke(
                UsersRawRepositoryMock::USER_UUID_TO_UPDATE,
                []
            );
            $this->fail("se esperaba GraphQlException");
        }
        catch (GraphQlException $e) {
            $this->assertEquals(ExceptionEnum::CODE_NOT_FOUND, $e->getCode());
        }
    }

    private function setAuthenticatedRequest(): void
    {
        HttpRequestMock::getInstance()
            ->setBearerToken(UsersRawRepositoryMock::USER_API_TOKEN_OK)
            ->getMockedInstance();
    }
}
