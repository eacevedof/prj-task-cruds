import { runWithTestDb } from "#tests/bootstrap.ts";

import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import { GraphQlException } from "#app/Modules/GraphQl/Domain/Exceptions/GraphQlException.ts";
import { AbstractTestCase } from "#tests/AbstractTestCase.ts";
import { HttpRequestMock } from "#tests/Modules/Shared/Infrastructure/Components/HttpRequestMock.ts";
import { TasksRawRepositoryMock } from "#tests/Modules/Shared/Infrastructure/Repositories/TasksRawRepositoryMock.ts";
import { UsersRawRepositoryMock } from "#tests/Modules/Shared/Infrastructure/Repositories/UsersRawRepositoryMock.ts";
import { SearchTasksByUserGraphQlControllerMother } from "#tests/Modules/Tasks/Infrastructure/Controllers/SearchTasksByUserGraphQlController/Mothers/SearchTasksByUserGraphQlControllerMother.ts";

runWithTestDb("search-tasks-by-user");

/**
 * Resolver de User.tasks probado directamente: por /graphql solo se llega con el User del propio token, asi que el
 * 404 de un usuario ajeno (lo que cubria la query de tasks por user_uuid, retirada en 3.0.0) solo se puede probar aqui.
 */
class SearchTasksByUserGraphQlControllerTest extends AbstractTestCase {

    testOkAsSuccessByOwnUserUuid(): void {
        this.#setAuthenticatedRequest();

        const taskPage = SearchTasksByUserGraphQlControllerMother.getControllerInstance().invoke(
            UsersRawRepositoryMock.USER_UUID_OK,
            {}
        );

        this.assertContains(
            TasksRawRepositoryMock.TASK_UUID_OK,
            (taskPage.items as Record<string, unknown>[]).map((task) => task.task_uuid)
        );
    }

    testNokAsNotFoundByOtherUserUuid(): void {
        this.#setAuthenticatedRequest();

        try {
            SearchTasksByUserGraphQlControllerMother.getControllerInstance().invoke(
                UsersRawRepositoryMock.USER_UUID_TO_UPDATE,
                {}
            );
            this.fail("se esperaba GraphQlException");
        }
        catch (e) {
            // el catch (GraphQlException) de PHP: lo demas (el fallo de fail() incluido) sigue su camino
            if (!(e instanceof GraphQlException)) throw e;
            this.assertEquals(ExceptionEnum.CODE_NOT_FOUND, e.getCode());
        }
    }

    #setAuthenticatedRequest(): void {
        HttpRequestMock.getInstance()
            .setBearerToken(UsersRawRepositoryMock.USER_API_TOKEN_OK)
            .getMockedInstance();
    }
}

AbstractTestCase.runTests(SearchTasksByUserGraphQlControllerTest);
