package mothers

import (
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/users/infrastructure/controllers"
)

func GetControllerInstance(httpRequester *httprequester.HttpRequester) *controllers.LogInUserController {
	return controllers.LogInUserControllerGetInstance(httpRequester)
}
