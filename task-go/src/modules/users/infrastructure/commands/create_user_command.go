package commands

import (
	"errors"

	sharedcommands "task-go/src/modules/shared/infrastructure/commands"
	sharedexceptions "task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/users/application/createuser"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
)

// CreateUserCommand ./console user-create --user_name=ana.lopez --user_email=ana@example.com --password=<password>
// Imprime la cuenta con su api_token en claro: es la unica vez que se ve.
type CreateUserCommand struct {
	sharedcommands.AbstractConsoleCommand
}

func CreateUserCommandGetInstance(input []string) *CreateUserCommand {
	return &CreateUserCommand{AbstractConsoleCommand: sharedcommands.NewAbstractConsoleCommand(input)}
}

func (c *CreateUserCommand) Run() {
	createUserResultDto, err := createuser.CreateUserServiceGetInstance().Invoke(
		createuser.CreateUserDtoFromPrimitives(map[string]any{
			"userName":  c.GetOptionValue(enums.UsersKeyEnum.USER_NAME),
			"userEmail": c.GetOptionValue(enums.UsersKeyEnum.USER_EMAIL),
			"password":  c.GetOptionValue(enums.UsersKeyEnum.PASSWORD),
		}),
	)
	if err == nil {
		c.PrintResult(createUserResultDto.ToArray())
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
		c.ExitWithUnexpectedError(err, "CreateUserCommand.Run")
	}
}
