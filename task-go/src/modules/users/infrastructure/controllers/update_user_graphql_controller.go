package controllers

import (
	"errors"

	graphqlcontrollers "task-go/src/modules/graphql/infrastructure/controllers"
	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedexceptions "task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/users/application/updateuser"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
)

// UpdateUserGraphqlController mutation updateUser (src/modules/users/infrastructure/providers/users.graphql). Exige Bearer.
type UpdateUserGraphqlController struct {
	graphqlcontrollers.AbstractGraphqlController
}

func UpdateUserGraphqlControllerGetInstance(httpRequester *httprequester.HttpRequester) *UpdateUserGraphqlController {
	return &UpdateUserGraphqlController{AbstractGraphqlController: graphqlcontrollers.NewAbstractGraphqlController(httpRequester)}
}

func (c *UpdateUserGraphqlController) Invoke(args map[string]any) (*entities.UserPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "UpdateUserGraphqlController.Invoke")
	}
	updateUserResultDto, err := updateuser.UpdateUserServiceGetInstance().Invoke(
		updateuser.UpdateUserDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{
				"userUuid":  args[enums.UsersKeyEnum.USER_UUID],
				"userName":  args[enums.UsersKeyEnum.USER_NAME],
				"userEmail": args[enums.UsersKeyEnum.USER_EMAIL],
			},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "UpdateUserGraphqlController.Invoke")
	}
	user := updateUserResultDto.ToArray()
	return &user, nil
}

// getGraphqlError los catch de task-php: FieldsException y UsersException son de dominio; lo demas, inesperado.
func (c *UpdateUserGraphqlController) getGraphqlError(err error, logTitle string) error {
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
