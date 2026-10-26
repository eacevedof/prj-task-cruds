package repositories

import "task-webassembly/src/modules/shared/infrastructure/components/graphqlrequester"

const (
	OPERATION_USERS = "users"
	QUERY_USERS     = `
query Users {
    users { user_uuid user_name user_email }
}`
)

type UsersReaderGraphqlRepository struct {
	graphqlRequester *graphqlrequester.GraphqlRequester
}

func UsersReaderGraphqlRepositoryGetInstance() *UsersReaderGraphqlRepository {
	return &UsersReaderGraphqlRepository{
		graphqlRequester: graphqlrequester.GetInstance(),
	}
}

// GetUsers query users: con un token, la lista solo trae a su usuario.
func (r *UsersReaderGraphqlRepository) GetUsers(apiToken string) (*graphqlrequester.ApiResponse, error) {
	return r.graphqlRequester.GetOperationResult(OPERATION_USERS, QUERY_USERS, nil, apiToken)
}
