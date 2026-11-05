package controllers

import (
	"errors"

	"task-go/src/core/config"
	graphqlexceptions "task-go/src/modules/graphql/domain/exceptions"
	"task-go/src/modules/shared/application"
	sharedenums "task-go/src/modules/shared/domain/enums"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	sharedexceptions "task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/users/application/updateuser"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
)

// UpdateUserGraphQlController mutation updateUser (src/modules/users/infrastructure/providers/users.graphql). Exige Bearer.
type UpdateUserGraphQlController struct {
	sharedcontrollers.AbstractHttpController
}

func UpdateUserGraphQlControllerGetInstance(httpRequester *httprequester.HttpRequester) *UpdateUserGraphQlController {
	return &UpdateUserGraphQlController{AbstractHttpController: sharedcontrollers.NewAbstractHttpController(httpRequester)}
}

func (c *UpdateUserGraphQlController) Invoke(gqlPayload map[string]any) (*entities.UserPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphQlError(err, "UpdateUserGraphQlController.Invoke")
	}
	updateUserResultDto, err := updateuser.UpdateUserServiceGetInstance().Invoke(
		updateuser.UpdateUserDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{
				"userUuid":  gqlPayload[enums.UsersKeyEnum.USER_UUID],
				"userName":  gqlPayload[enums.UsersKeyEnum.USER_NAME],
				"userEmail": gqlPayload[enums.UsersKeyEnum.USER_EMAIL],
			},
		)),
	)
	if err != nil {
		return nil, c.getGraphQlError(err, "UpdateUserGraphQlController.Invoke")
	}
	user := updateUserResultDto.ToArray()
	return &user, nil
}

// getGraphQlError los dos catch de task-php: FieldsException y UsersException -> ClientAwareExceptionForWebonyx (su
// codigo y su mensaje al cliente); lo demas -> log + mail + InternalServerError (500 con el mensaje generico).
func (c *UpdateUserGraphQlController) getGraphQlError(err error, logTitle string) error {
	var fieldsException *sharedexceptions.FieldsException
	var usersException *exceptions.UsersException
	switch {
	case errors.As(err, &fieldsException):
		return graphqlexceptions.ClientAwareExceptionForWebonyx(fieldsException)
	case errors.As(err, &usersException):
		return graphqlexceptions.ClientAwareExceptionForWebonyx(usersException)
	default:
		c.LogException(err, logTitle)
		c.SendMailOnThrowable(err, logTitle)
		return graphqlexceptions.InternalServerError(config.Tr(sharedenums.SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
	}
}
