package searchtasksbyusergraphqlcontroller

import (
	"errors"
	"os"
	"testing"

	graphqlexceptions "task-go/src/modules/graphql/domain/exceptions"
	"task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/tests"
	"task-go/tests/modules/shared/infrastructure/components"
	"task-go/tests/modules/shared/infrastructure/repositories"
	"task-go/tests/modules/tasks/infrastructure/controllers/searchtasksbyusergraphqlcontroller/mothers"
)

// Resolver de User.tasks probado directamente: por /graphql solo se llega con el User del propio token, asi que el
// 404 de un usuario ajeno (lo que cubria la query de tasks por user_uuid, retirada en 3.0.0) solo se puede probar aqui.

func TestMain(m *testing.M) {
	os.Exit(tests.RunWithTestDb(m, "search-tasks-by-user"))
}

func TestOkAsSuccessByOwnUserUuid(t *testing.T) {
	taskPage, err := mothers.GetControllerInstance(getAuthenticatedRequest()).Invoke(
		repositories.UsersRawRepositoryMock.USER_UUID_OK,
		map[string]any{},
	)

	tests.AssertNil(t, err)
	taskUuids := []string{}
	for _, task := range taskPage.Items {
		taskUuids = append(taskUuids, task.TaskUuid)
	}
	tests.AssertContains(t, repositories.TasksRawRepositoryMock.TASK_UUID_OK, taskUuids)
}

func TestNokAsNotFoundByOtherUserUuid(t *testing.T) {
	_, err := mothers.GetControllerInstance(getAuthenticatedRequest()).Invoke(
		repositories.UsersRawRepositoryMock.USER_UUID_TO_UPDATE,
		map[string]any{},
	)

	var graphQlException *graphqlexceptions.GraphQlException
	if !errors.As(err, &graphQlException) {
		t.Fatalf("se esperaba GraphQlException, llego %#v", err)
	}
	tests.AssertEquals(t, enums.ExceptionEnum.CODE_NOT_FOUND, graphQlException.GetCode())
}

func getAuthenticatedRequest() *httprequester.HttpRequester {
	httpRequester, _ := components.GetInstance().
		SetBearerToken(repositories.UsersRawRepositoryMock.USER_API_TOKEN_OK).
		GetMockedInstance()
	return httpRequester
}
