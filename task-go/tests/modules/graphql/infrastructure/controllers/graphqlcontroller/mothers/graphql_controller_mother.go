package mothers

import (
	"task-go/src/core/routes"
	graphqlcontrollers "task-go/src/modules/graphql/infrastructure/controllers"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
)

func GetControllerInstance(httpRequester *httprequester.HttpRequester) *graphqlcontrollers.ExecuteGraphQlQueryController {
	return graphqlcontrollers.ExecuteGraphQlQueryControllerGetInstance(httpRequester, routes.GetGraphQlSchemaProvidersRegistry())
}
