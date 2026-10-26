package commands

import (
	"errors"

	"task-go/src/modules/shared/application"
	sharedcommands "task-go/src/modules/shared/infrastructure/commands"
	"task-go/src/modules/users/application/deleteuser"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
	"task-go/src/modules/users/infrastructure/traits"
)

// DeleteUserCommand ./console user-delete --user_uuid=<uuid>
// Borrado fisico: se lleva sus tareas (FK ON DELETE CASCADE). No pide confirmacion.
// Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid
// (ConsoleAuthUserTrait).
type DeleteUserCommand struct {
	sharedcommands.AbstractConsoleCommand
	traits.ConsoleAuthUserTrait
}

func DeleteUserCommandGetInstance(input []string) *DeleteUserCommand {
	return &DeleteUserCommand{AbstractConsoleCommand: sharedcommands.NewAbstractConsoleCommand(input)}
}

func (c *DeleteUserCommand) Run() {
	userUuid := c.GetOptionValue(enums.UsersKeyEnum.USER_UUID)
	deleteUserResultDto, err := c.getDeleteUserResultDto(userUuid)
	if err == nil {
		c.PrintResult(deleteUserResultDto.ToArray())
		return
	}

	var usersException *exceptions.UsersException
	if errors.As(err, &usersException) {
		c.ExitWithDomainError(usersException)
	}
	c.ExitWithUnexpectedError(err, "DeleteUserCommand.Run")
}

func (c *DeleteUserCommand) getDeleteUserResultDto(userUuid string) (*deleteuser.DeleteUserResultDto, error) {
	authUserPrimitives, err := c.TryToGetConsoleAuthUserPrimitivesOrFail(userUuid)
	if err != nil {
		return nil, err
	}
	return deleteuser.DeleteUserServiceGetInstance().Invoke(
		deleteuser.DeleteUserDtoFromPrimitives(application.MergePrimitives(
			authUserPrimitives,
			map[string]any{"userUuid": userUuid},
		)),
	)
}
