package modules.tasks.infrastructure.controllers.searchtasksbyusergraphqlcontroller;

import java.util.ArrayList;
import java.util.List;
import java.util.Map;

import org.junit.jupiter.api.BeforeAll;
import org.junit.jupiter.api.Test;

import modules.shared.application.Primitives;
import modules.shared.domain.enums.ExceptionEnum;
import modules.graphql.domain.exceptions.GraphQlException;
import modules.shared.infrastructure.components.HttpRequestMock;
import modules.shared.infrastructure.repositories.TasksRawRepositoryMock;
import modules.shared.infrastructure.repositories.UsersRawRepositoryMock;
import modules.tasks.infrastructure.controllers.searchtasksbyusergraphqlcontroller.mothers.SearchTasksByUserGraphQlControllerMother;

import testcase.AbstractTestCase;
import testcase.Bootstrap;

/**
 * Resolver de User.tasks probado directamente: por /graphql solo se llega con el User del propio token, asi que el
 * 404 de un usuario ajeno (lo que cubria la query de tasks por user_uuid, retirada en 3.0.0) solo se puede probar aqui.
 */
class SearchTasksByUserGraphQlControllerTest extends AbstractTestCase {

    @BeforeAll
    static void setUpTestDb() {
        Bootstrap.runWithTestDb("search-tasks-by-user");
    }

    @Test
    void testOkAsSuccessByOwnUserUuid() {
        setAuthenticatedRequest();

        Map<String, Object> taskPage = SearchTasksByUserGraphQlControllerMother.getControllerInstance().invoke(
            UsersRawRepositoryMock.USER_UUID_OK,
            Primitives.of()
        );

        assertContains(TasksRawRepositoryMock.TASK_UUID_OK, getTaskUuids(taskPage));
    }

    @Test
    void testNokAsNotFoundByOtherUserUuid() {
        setAuthenticatedRequest();

        try {
            SearchTasksByUserGraphQlControllerMother.getControllerInstance().invoke(
                UsersRawRepositoryMock.USER_UUID_TO_UPDATE,
                Primitives.of()
            );
            fail("se esperaba GraphQlException");
        }
        catch (GraphQlException e) {
            assertEquals(ExceptionEnum.CODE_NOT_FOUND, e.getCode());
        }
    }

    private void setAuthenticatedRequest() {
        HttpRequestMock.getInstance()
            .setBearerToken(UsersRawRepositoryMock.USER_API_TOKEN_OK)
            .getMockedInstance();
    }

    /**
     * array_column($taskPage["items"], "task_uuid")
     */
    private List<Object> getTaskUuids(Map<String, Object> taskPage) {
        List<Object> taskUuids = new ArrayList<>();
        for (Map<String, Object> item : Primitives.getPrimitivesList(taskPage.get("items"))) {
            taskUuids.add(item.get("task_uuid"));
        }
        return taskUuids;
    }
}
