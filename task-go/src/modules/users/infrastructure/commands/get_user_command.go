package commands

import (
	"errors"

	"task-go/src/modules/shared/application"
	sharedcommands "task-go/src/modules/shared/infrastructure/commands"
	"task-go/src/modules/users/application/getuser"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
	"task-go/src/modules/users/infrastructure/traits"
)

// GetUserCommand ./console user-get --user_uuid=<uuid>
// Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid
// (ConsoleAuthUserTrait).
type GetUserCommand struct {
	sharedcommands.AbstractConsoleCommand
	traits.ConsoleAuthUserTrait
}

func GetUserCommandGetInstance(input []string) *GetUserCommand {
	return &GetUserCommand{AbstractConsoleCommand: sharedcommands.NewAbstractConsoleCommand(input)}
}

func (c *GetUserCommand) Run() {
	userUuid := c.GetOptionValue(enums.UsersKeyEnum.USER_UUID)
	getUserResultDto, err := c.getGetUserResultDto(userUuid)
	if err == nil {
		c.PrintResult(getUserResultDto.ToArray())
		return
	}

	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		c.ExitWithDomainError(usersException)
	}
	c.ExitWithUnexpectedError(err, "GetUserCommand.Run")
}

func (c *GetUserCommand) getGetUserResultDto(userUuid string) (*getuser.GetUserResultDto, error) {
	authUserPrimitives, err := c.TryToGetConsoleAuthUserPrimitivesOrFail(userUuid)
	if err != nil {
		return nil, err
	}
	return getuser.GetUserServiceGetInstance().Invoke(
		getuser.GetUserDtoFromPrimitives(application.MergePrimitives(
			authUserPrimitives,
			map[string]any{"userUuid": userUuid},
		)),
	)
}
