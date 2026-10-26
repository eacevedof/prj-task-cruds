package restoresession

import (
	"task-webassembly/src/modules/shared/application"
	"task-webassembly/src/modules/users/domain/enums"
	"task-webassembly/src/modules/users/domain/exceptions"
	"task-webassembly/src/modules/users/infrastructure/repositories"
)

// RestoreSessionService recuperar la sesion guardada al recargar: el api_token se comprueba contra la API (pudo
// rotarse en otro login o borrarse la cuenta). Si es valido, la query users devuelve su usuario.
type RestoreSessionService struct {
	usersReaderGraphqlRepository *repositories.UsersReaderGraphqlRepository
}

func RestoreSessionServiceGetInstance() *RestoreSessionService {
	return &RestoreSessionService{
		usersReaderGraphqlRepository: repositories.UsersReaderGraphqlRepositoryGetInstance(),
	}
}

// Invoke error: *UsersException o uno inesperado (red, respuesta que no es JSON).
func (s *RestoreSessionService) Invoke(restoreSessionDto *RestoreSessionDto) (*RestoreSessionResultDto, error) {
	apiToken := restoreSessionDto.GetApiToken()
	if apiToken == "" {
		return nil, exceptions.BadRequestCustom([]string{enums.UsersMessageEnum.API_TOKEN_IS_REQUIRED})
	}

	apiResponse, err := s.usersReaderGraphqlRepository.GetUsers(apiToken)
	if err != nil {
		return nil, err
	}
	if !apiResponse.Status {
		return nil, exceptions.FromApiResponse(apiResponse)
	}

	user := map[string]any{}
	if users := application.ToMaps(apiResponse.Data); len(users) > 0 {
		user = users[0]
	}
	return RestoreSessionResultDtoFromPrimitives(map[string]any{
		"apiToken":  apiToken,
		"userUuid":  user[enums.UsersKeyEnum.USER_UUID],
		"userName":  user[enums.UsersKeyEnum.USER_NAME],
		"userEmail": user[enums.UsersKeyEnum.USER_EMAIL],
	}), nil
}
