package mothers

import (
	"task-go/src/core/graphql"
	"task-go/src/modules/shared/infrastructure/components/httprequester"
)

func GetControllerInstance(httpRequester *httprequester.HttpRequester) *graphql.GraphqlController {
	return graphql.GraphqlControllerGetInstance(httpRequester)
}
