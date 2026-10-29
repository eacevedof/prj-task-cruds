package mothers

import (
	"strings"

	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/tests/modules/shared/infrastructure/components"
	"task-go/tests/modules/shared/infrastructure/repositories"
)

type LogInUserRequestMother struct{}

func LogInUserRequestMotherGetInstance() *LogInUserRequestMother {
	return &LogInUserRequestMother{}
}

// GetRequestByTestCase el HttpRequester de la peticion (LogInUserRestController.LogIn() no tiene argumentos: todo va en
// el body).
func (m *LogInUserRequestMother) GetRequestByTestCase(useCase LogInUserTestCaseEnum) *httprequester.HttpRequester {
	users := repositories.UsersRawRepositoryMock
	switch useCase {
	// user_name no distingue mayusculas
	case OK_AS_SUCCESS_BY_VALID_CREDENTIALS_WITH_OTHER_CASE:
		return m.getRequest(strings.ToUpper(users.USER_NAME_TO_LOG_IN), users.USER_PASSWORD_TO_LOG_IN)

	case NOK_AS_UNAUTHORIZED_BY_WRONG_PASSWORD:
		return m.getRequest(users.USER_NAME_TO_LOG_IN, users.USER_PASSWORD_NOK)

	case NOK_AS_UNAUTHORIZED_BY_UNKNOWN_USER_NAME:
		return m.getRequest(users.USER_NAME_NOK, users.USER_PASSWORD_TO_LOG_IN)

	// cuenta sin password (como las creadas antes de add-password-hash-to-users): no puede entrar
	case NOK_AS_UNAUTHORIZED_BY_USER_WITHOUT_PASSWORD:
		return m.getRequest(users.USER_NAME_OK, users.USER_PASSWORD_TO_LOG_IN)
	}
	panic("unknown test case " + string(useCase))
}

func (m *LogInUserRequestMother) getRequest(userName string, password string) *httprequester.HttpRequester {
	httpRequester, _ := components.GetInstance().
		SetPostRawParams(map[string]any{"user_name": userName, "password": password}).
		GetMockedInstance()
	return httpRequester
}
