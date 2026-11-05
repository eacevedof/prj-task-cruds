package mothers

import (
	"task-go/src/modules/shared/infrastructure/components/httprequester"
	"task-go/src/modules/tasks/infrastructure/controllers"
)

func GetControllerInstance(httpRequester *httprequester.HttpRequester) *controllers.SearchTasksByUserGraphQlController {
	return controllers.SearchTasksByUserGraphQlControllerGetInstance(httpRequester)
}
