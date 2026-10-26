package traits

import "task-go/src/modules/users/application/authenticateconsoleuser"

// ConsoleAuthUserTrait quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por su
// user_uuid, sin token.
type ConsoleAuthUserTrait struct{}

// TryToGetConsoleAuthUserPrimitivesOrFail error: *UsersException (404) o uno inesperado.
func (t ConsoleAuthUserTrait) TryToGetConsoleAuthUserPrimitivesOrFail(userUuid string) (map[string]any, error) {
	authenticateUserResultDto, err := authenticateconsoleuser.AuthenticateConsoleUserServiceGetInstance().Invoke(
		authenticateconsoleuser.AuthenticateConsoleUserDtoFromPrimitives(map[string]any{"userUuid": userUuid}),
	)
	if err != nil {
		return nil, err
	}
	return authenticateUserResultDto.ToAuthPrimitives(), nil
}
