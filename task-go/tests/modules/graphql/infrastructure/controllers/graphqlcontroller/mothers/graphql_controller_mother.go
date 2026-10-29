package mothers

import (
	"task-go/src/core/routes"
	"task-go/src/modules/graphql/infrastructure/controllers/graphqlcontroller"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
)

func GetControllerInstance(httpRequester *httprequester.HttpRequester) *graphqlcontroller.GraphqlController {
	return graphqlcontroller.GraphqlControllerGetInstance(httpRequester, routes.GetGraphqlSchemaRegistry())
}
