package regenerateapitoken

import (
	"task-webassembly/src/modules/account/domain/exceptions"
	"task-webassembly/src/modules/account/infrastructure/repositories"
	"task-webassembly/src/modules/shared/application"
	usersenums "task-webassembly/src/modules/users/domain/enums"
)

// RegenerateApiTokenService rota el api_token: el actual deja de valer en el acto y el nuevo solo se ve en esta respuesta.
type RegenerateApiTokenService struct {
	accountWriterGraphqlRepository *repositories.AccountWriterGraphqlRepository
}

func RegenerateApiTokenServiceGetInstance() *RegenerateApiTokenService {
	return &RegenerateApiTokenService{
		accountWriterGraphqlRepository: repositories.AccountWriterGraphqlRepositoryGetInstance(),
	}
}

// Invoke error: *AccountException o uno inesperado (red, respuesta que no es JSON).
func (s *RegenerateApiTokenService) Invoke(regenerateApiTokenDto *RegenerateApiTokenDto) (*RegenerateApiTokenResultDto, error) {
	apiResponse, err := s.accountWriterGraphqlRepository.RegenerateApiTokenByUserUuid(
		regenerateApiTokenDto.GetUserUuid(),
		regenerateApiTokenDto.GetApiToken(),
	)
	if err != nil {
		return nil, err
	}
	if !apiResponse.Status {
		return nil, exceptions.FromApiResponse(apiResponse)
	}

	user := application.ToMap(apiResponse.Data)
	return RegenerateApiTokenResultDtoFromPrimitives(map[string]any{
		"userUuid": user[usersenums.UsersKeyEnum.USER_UUID],
		"apiToken": user[usersenums.UsersKeyEnum.API_TOKEN],
	}), nil
}
