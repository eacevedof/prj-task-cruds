package controllers

import (
	"errors"

	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	sharedcontrollers "task-go/src/modules/shared/infrastructure/controllers"
	sharedexceptions "task-go/src/modules/shared/infrastructure/exceptions"
	"task-go/src/modules/users/application/createuser"
	"task-go/src/modules/users/application/deleteuser"
	"task-go/src/modules/users/application/getuser"
	"task-go/src/modules/users/application/listusers"
	"task-go/src/modules/users/application/loginuser"
	"task-go/src/modules/users/application/regenerateuserapitoken"
	"task-go/src/modules/users/application/updateuser"
	"task-go/src/modules/users/domain/entities"
	"task-go/src/modules/users/domain/enums"
	"task-go/src/modules/users/domain/exceptions"
)

// UsersGraphqlController resolvers GraphQL de users (src/core/graphql/schema.graphql). Todo exige Bearer salvo
// createUser (alta abierta, devuelve el primer api_token) y logIn.
// Cada resolver devuelve el ResultDto o un *GraphqlError: el del dominio con su codigo, o el 500 de lo inesperado.
type UsersGraphqlController struct {
	sharedcontrollers.AbstractGraphqlController
}

func UsersGraphqlControllerGetInstance(httpRequester *httprequester.HttpRequester) *UsersGraphqlController {
	return &UsersGraphqlController{AbstractGraphqlController: sharedcontrollers.NewAbstractGraphqlController(httpRequester)}
}

func (c *UsersGraphqlController) GetUsers() ([]entities.UserPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "UsersGraphqlController.GetUsers")
	}
	listUsersResultDto, err := listusers.ListUsersServiceGetInstance().Invoke(
		listusers.ListUsersDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "UsersGraphqlController.GetUsers")
	}
	return listUsersResultDto.ToArray(), nil
}

func (c *UsersGraphqlController) GetUser(args map[string]any) (*entities.UserPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "UsersGraphqlController.GetUser")
	}
	getUserResultDto, err := getuser.GetUserServiceGetInstance().Invoke(
		getuser.GetUserDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{"userUuid": args[enums.UsersKeyEnum.USER_UUID]},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "UsersGraphqlController.GetUser")
	}
	user := getUserResultDto.ToArray()
	return &user, nil
}

func (c *UsersGraphqlController) CreateUser(args map[string]any) (*entities.UserWithApiTokenPrimitives, error) {
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
		return nil, c.getGraphqlError(err, "UsersGraphqlController.CreateUser")
	}
	user := createUserResultDto.ToArray()
	return &user, nil
}

// LogIn publico (sin token): user_name + password -> el usuario con un api_token nuevo.
func (c *UsersGraphqlController) LogIn(args map[string]any) (*entities.UserWithApiTokenPrimitives, error) {
	logInUserResultDto, err := loginuser.LogInUserServiceGetInstance().Invoke(
		loginuser.LogInUserDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			map[string]any{
				"userName": args[enums.UsersKeyEnum.USER_NAME],
				"password": args[enums.UsersKeyEnum.PASSWORD],
			},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "UsersGraphqlController.LogIn")
	}
	user := logInUserResultDto.ToArray()
	return &user, nil
}

func (c *UsersGraphqlController) UpdateUser(args map[string]any) (*entities.UserPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "UsersGraphqlController.UpdateUser")
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
		return nil, c.getGraphqlError(err, "UsersGraphqlController.UpdateUser")
	}
	user := updateUserResultDto.ToArray()
	return &user, nil
}

func (c *UsersGraphqlController) DeleteUser(args map[string]any) (*deleteuser.DeleteUserResultPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "UsersGraphqlController.DeleteUser")
	}
	deleteUserResultDto, err := deleteuser.DeleteUserServiceGetInstance().Invoke(
		deleteuser.DeleteUserDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{"userUuid": args[enums.UsersKeyEnum.USER_UUID]},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "UsersGraphqlController.DeleteUser")
	}
	deleteUserResult := deleteUserResultDto.ToArray()
	return &deleteUserResult, nil
}

func (c *UsersGraphqlController) RegenerateApiToken(
	args map[string]any,
) (*regenerateuserapitoken.RegenerateUserApiTokenResultPrimitives, error) {
	authUserPrimitives, err := c.GetAuthUserOrFail()
	if err != nil {
		return nil, c.getGraphqlError(err, "UsersGraphqlController.RegenerateApiToken")
	}
	regenerateUserApiTokenResultDto, err := regenerateuserapitoken.RegenerateUserApiTokenServiceGetInstance().Invoke(
		regenerateuserapitoken.RegenerateUserApiTokenDtoFromPrimitives(application.MergePrimitives(
			c.GetHttpRequest().GetMetadata(),
			authUserPrimitives,
			map[string]any{"userUuid": args[enums.UsersKeyEnum.USER_UUID]},
		)),
	)
	if err != nil {
		return nil, c.getGraphqlError(err, "UsersGraphqlController.RegenerateApiToken")
	}
	apiTokenResult := regenerateUserApiTokenResultDto.ToArray()
	return &apiTokenResult, nil
}

// getGraphqlError los catch de task-php: FieldsException y UsersException son de dominio; lo demas, inesperado.
func (c *UsersGraphqlController) getGraphqlError(err error, logTitle string) error {
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
