package commands

import (
	"errors"

	"task-go/src/modules/shared/application"
	sharedcommands "task-go/src/modules/shared/infrastructure/commands"
	"task-go/src/modules/users/application/regenerateuserapitoken"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
	"task-go/src/modules/users/infrastructure/traits"
)

// RegenerateUserApiTokenCommand ./console user-regenerate-api-token --user_uuid=<uuid>
// El token anterior deja de valer; el nuevo se imprime en claro solo aqui.
// Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid
// (ConsoleAuthUserTrait).
type RegenerateUserApiTokenCommand struct {
	sharedcommands.AbstractConsoleCommand
	traits.ConsoleAuthUserTrait
}

func RegenerateUserApiTokenCommandGetInstance(input []string) *RegenerateUserApiTokenCommand {
	return &RegenerateUserApiTokenCommand{AbstractConsoleCommand: sharedcommands.NewAbstractConsoleCommand(input)}
}

func (c *RegenerateUserApiTokenCommand) Run() {
	userUuid := c.GetOptionValue(enums.UsersKeyEnum.USER_UUID)
	regenerateUserApiTokenResultDto, err := c.getRegenerateUserApiTokenResultDto(userUuid)
	if err == nil {
		c.PrintResult(regenerateUserApiTokenResultDto.ToArray())
		return
	}

	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		c.ExitWithDomainError(usersException)
	}
	c.ExitWithUnexpectedError(err, "RegenerateUserApiTokenCommand.Run")
}

func (c *RegenerateUserApiTokenCommand) getRegenerateUserApiTokenResultDto(
	userUuid string,
) (*regenerateuserapitoken.RegenerateUserApiTokenResultDto, error) {
	authUserPrimitives, err := c.TryToGetConsoleAuthUserPrimitivesOrFail(userUuid)
	if err != nil {
		return nil, err
	}
	return regenerateuserapitoken.RegenerateUserApiTokenServiceGetInstance().Invoke(
		regenerateuserapitoken.RegenerateUserApiTokenDtoFromPrimitives(application.MergePrimitives(
			authUserPrimitives,
			map[string]any{"userUuid": userUuid},
		)),
	)
}
