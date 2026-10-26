package deleteaccount

import (
	"task-webassembly/src/modules/account/domain/exceptions"
	"task-webassembly/src/modules/account/infrastructure/repositories"
	"task-webassembly/src/modules/shared/application"
	usersenums "task-webassembly/src/modules/users/domain/enums"
)

// DeleteAccountService borra la cuenta del usuario del token; la API borra con ella todas sus tareas.
type DeleteAccountService struct {
	accountWriterGraphqlRepository *repositories.AccountWriterGraphqlRepository
}

func DeleteAccountServiceGetInstance() *DeleteAccountService {
	return &DeleteAccountService{
		accountWriterGraphqlRepository: repositories.AccountWriterGraphqlRepositoryGetInstance(),
	}
}

// Invoke error: *AccountException o uno inesperado (red, respuesta que no es JSON).
func (s *DeleteAccountService) Invoke(deleteAccountDto *DeleteAccountDto) (*DeleteAccountResultDto, error) {
	apiResponse, err := s.accountWriterGraphqlRepository.DeleteUserByUserUuid(
		deleteAccountDto.GetUserUuid(),
		deleteAccountDto.GetApiToken(),
	)
	if err != nil {
		return nil, err
	}
	if !apiResponse.Status {
		return nil, exceptions.FromApiResponse(apiResponse)
	}

	return DeleteAccountResultDtoFromPrimitives(map[string]any{
		"userUuid": application.ToMap(apiResponse.Data)[usersenums.UsersKeyEnum.USER_UUID],
	}), nil
}
