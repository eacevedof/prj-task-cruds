package controllers

import (
	"errors"

	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	sharedexceptions "task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/users/application/createuser"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
)

// CreateUserGraphqlController mutation createUser (src/core/graphql/schema.graphql): alta abierta (sin token), devuelve
// el primer api_token.
type CreateUserGraphqlController struct {
	sharedcontrollers.AbstractGraphqlController
}

func CreateUserGraphqlControllerGetInstance(httpRequester *httprequester.HttpRequester) *CreateUserGraphqlController {
	return &CreateUserGraphqlController{AbstractGraphqlController: sharedcontrollers.NewAbstractGraphqlController(httpRequester)}
}

func (c *CreateUserGraphqlController) Invoke(args map[string]any) (*entities.UserWithApiTokenPrimitives, error) {
	createUserResultDto, err := createuser.CreateUserServiceGetInstance().Invoke(
		createuser.CreateUserDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			map[string]any{
				"userName":  args[enums.UsersKeyEnum.USER_NAME],
				"userEmail": args[enums.UsersKeyEnum.USER_EMAIL],
				"password":  args[enums.UsersKeyEnum.PASSWORD],
			},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "CreateUserGraphqlController.Invoke")
	}
	user := createUserResultDto.ToArray()
	return &user, nil
}

// getGraphqlError los catch de task-php: FieldsException y UsersException son de dominio; lo demas, inesperado.
func (c *CreateUserGraphqlController) getGraphqlError(err error, logTitle string) error {
	var fieldsException *sharedexceptions.FieldsException
	var usersException *exceptions.UsersException
	switch {
	case errors.As(err, &fieldsException):
		return c.GetGraphqlDomainError(fieldsException)
	case errors.As(err, &usersException):
		return c.GetGraphqlDomainError(usersException)
	default:
		return c.GetGraphqlUnexpectedError(err, logTitle)
	}
}
