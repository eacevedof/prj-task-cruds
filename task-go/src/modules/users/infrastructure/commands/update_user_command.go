package commands

import (
	"errors"

	"task-go/src/modules/shared/application"
	sharedcommands "task-go/src/modules/shared/infrastructure/commands"
	sharedexceptions "task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/users/application/updateuser"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
	"task-go/src/modules/users/infrastructure/traits"
)

// UpdateUserCommand ./console user-update --user_uuid=<uuid> --user_name=ana.lopez --user_email=ana@example.com
// Reemplazo completo, como el PUT: user_name y user_email obligatorios.
// Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid
// (ConsoleAuthUserTrait).
type UpdateUserCommand struct {
	sharedcommands.AbstractConsoleCommand
	traits.ConsoleAuthUserTrait
}

func UpdateUserCommandGetInstance(input []string) *UpdateUserCommand {
	return &UpdateUserCommand{AbstractConsoleCommand: sharedcommands.NewAbstractConsoleCommand(input)}
}

func (c *UpdateUserCommand) Run() {
	userUuid := c.GetOptionValue(enums.UsersKeyEnum.USER_UUID)
	updateUserResultDto, err := c.getUpdateUserResultDto(userUuid)
	if err == nil {
		c.PrintResult(updateUserResultDto.ToArray())
		return
	}

	var fieldsException *sharedexceptions.FieldsException
	var usersException *exceptions.UsersException
	switch {
	case errors.As(err, &fieldsException):
		c.ExitWithDomainError(fieldsException)
	case errors.As(err, &usersException):
		c.ExitWithDomainError(usersException)
	default:
		c.ExitWithUnexpectedError(err, "UpdateUserCommand.Run")
	}
}

func (c *UpdateUserCommand) getUpdateUserResultDto(userUuid string) (*updateuser.UpdateUserResultDto, error) {
	authUserPrimitives, err := c.TryToGetConsoleAuthUserPrimitivesOrFail(userUuid)
	if err != nil {
		return nil, err
	}
	return updateuser.UpdateUserServiceGetInstance().Invoke(
		updateuser.UpdateUserDtoFromPrimitives(application.MergePrimitives(
			authUserPrimitives,
			map[string]any{
				"userUuid":  userUuid,
				"userName":  c.GetOptionValue(enums.UsersKeyEnum.USER_NAME),
				"userEmail": c.GetOptionValue(enums.UsersKeyEnum.USER_EMAIL),
			},
		)),
	)
}
