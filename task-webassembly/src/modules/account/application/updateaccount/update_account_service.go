package updateaccount

import (
	"task-webassembly/src/modules/account/domain/enums"
	"task-webassembly/src/modules/account/domain/exceptions"
	"task-webassembly/src/modules/account/infrastructure/repositories"
	"task-webassembly/src/modules/shared/application"
	usersenums "task-webassembly/src/modules/users/domain/enums"
)

type UpdateAccountService struct {
	accountWriterGraphqlRepository *repositories.AccountWriterGraphqlRepository
}

func UpdateAccountServiceGetInstance() *UpdateAccountService {
	return &UpdateAccountService{
		accountWriterGraphqlRepository: repositories.AccountWriterGraphqlRepositoryGetInstance(),
	}
}

// Invoke error: *AccountException o uno inesperado (red, respuesta que no es JSON).
func (s *UpdateAccountService) Invoke(updateAccountDto *UpdateAccountDto) (*UpdateAccountResultDto, error) {
	errors := []string{}
	if updateAccountDto.GetUserName() == "" {
		errors = append(errors, enums.AccountMessageEnum.USER_NAME_IS_REQUIRED)
	}
	if updateAccountDto.GetUserEmail() == "" {
		errors = append(errors, enums.AccountMessageEnum.USER_EMAIL_IS_REQUIRED)
	}
	if len(errors) > 0 {
		return nil, exceptions.BadRequestCustom(errors)
	}

	apiResponse, err := s.accountWriterGraphqlRepository.UpdateUserByUserUuid(
		updateAccountDto.GetUserUuid(),
		updateAccountDto.GetUserName(),
		updateAccountDto.GetUserEmail(),
		updateAccountDto.GetApiToken(),
	)
	if err != nil {
		return nil, err
	}
	if !apiResponse.Status {
		return nil, exceptions.FromApiResponse(apiResponse)
	}

	user := application.ToMap(apiResponse.Data)
	return UpdateAccountResultDtoFromPrimitives(map[string]any{
		"userUuid":  user[usersenums.UsersKeyEnum.USER_UUID],
		"userName":  user[usersenums.UsersKeyEnum.USER_NAME],
		"userEmail": user[usersenums.UsersKeyEnum.USER_EMAIL],
	}), nil
}
