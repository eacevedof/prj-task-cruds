package regenerateuserapitoken

import (
	"task-go/src/core/config"
	"task-go/src/modules/shared/infrastructure/components/datetimer"
	"task-go/src/modules/shared/infrastructure/components/token"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
	"task-go/src/modules/users/infrastructure/repositories"
)

// RegenerateUserApiTokenService rota el token del propio usuario (p.ej. si se ha filtrado): el anterior deja de valer
// y el nuevo se devuelve en claro solo en esta respuesta.
type RegenerateUserApiTokenService struct {
	dateTimer                   *datetimer.DateTimer
	tokenGenerator              *token.TokenGenerator
	usersWriterSqliteRepository *repositories.UsersWriterSqliteRepository

	regenerateUserApiTokenDto *RegenerateUserApiTokenDto
}

func RegenerateUserApiTokenServiceGetInstance() *RegenerateUserApiTokenService {
	return &RegenerateUserApiTokenService{
		dateTimer:                   datetimer.GetInstance(),
		tokenGenerator:              token.GetInstance(),
		usersWriterSqliteRepository: repositories.UsersWriterSqliteRepositoryGetInstance(),
	}
}

// Invoke error: *UsersException (401, 404) o uno inesperado.
func (s *RegenerateUserApiTokenService) Invoke(
	regenerateUserApiTokenDto *RegenerateUserApiTokenDto,
) (*RegenerateUserApiTokenResultDto, error) {
	s.regenerateUserApiTokenDto = regenerateUserApiTokenDto
	if err := s.failIfWrongInput(); err != nil {
		return nil, err
	}

	apiToken := s.tokenGenerator.GetNewToken()
	_, err := s.usersWriterSqliteRepository.UpdateApiTokenHashById(
		entities.UserEntityFromPrimitives(map[string]any{
			enums.UsersKeyEnum.ID:             regenerateUserApiTokenDto.GetAuthUserId(),
			enums.UsersKeyEnum.API_TOKEN_HASH: s.tokenGenerator.GetTokenHash(apiToken),
			enums.UsersKeyEnum.UPDATED_AT:     s.dateTimer.GetNowDtIntoTargetTz(),
		}),
	)
	if err != nil {
		return nil, err
	}

	return RegenerateUserApiTokenResultDtoFromPrimitives(map[string]any{
		"userUuid": regenerateUserApiTokenDto.GetUserUuid(),
		"apiToken": apiToken,
	}), nil
}

// failIfWrongInput solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
func (s *RegenerateUserApiTokenService) failIfWrongInput() error {
	if s.regenerateUserApiTokenDto.GetAuthUserId() < 1 {
		return exceptions.UnauthorizedCustom(config.Tr(enums.UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN))
	}
	userUuid := s.regenerateUserApiTokenDto.GetUserUuid()
	if userUuid != s.regenerateUserApiTokenDto.GetAuthUserUuid() {
		return exceptions.NotFoundCustom(config.Tr(enums.UsersMessageEnum.USER_X_NOT_FOUND, userUuid))
	}
	return nil
}
