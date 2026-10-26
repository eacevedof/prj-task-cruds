package repositories

import "task-webassembly/src/modules/shared/infrastructure/components/graphqlrequester"

const (
	OPERATION_CREATE_USER = "createUser"
	MUTATION_CREATE_USER  = `
mutation CreateUser($userName: String!, $userEmail: String!, $password: String!) {
    createUser(user_name: $userName, user_email: $userEmail, password: $password) { user_uuid user_name user_email api_token }
}`

	OPERATION_LOG_IN = "logIn"
	MUTATION_LOG_IN  = `
mutation LogIn($userName: String!, $password: String!) {
    logIn(user_name: $userName, password: $password) { user_uuid user_name user_email api_token }
}`
)

type UsersWriterGraphqlRepository struct {
	graphqlRequester *graphqlrequester.GraphqlRequester
}

func UsersWriterGraphqlRepositoryGetInstance() *UsersWriterGraphqlRepository {
	return &UsersWriterGraphqlRepository{
		graphqlRequester: graphqlrequester.GetInstance(),
	}
}

// CreateUser mutation createUser (alta abierta, sin token)
func (r *UsersWriterGraphqlRepository) CreateUser(userName string, userEmail string, password string) (*graphqlrequester.ApiResponse, error) {
	return r.graphqlRequester.GetOperationResult(
		OPERATION_CREATE_USER,
		MUTATION_CREATE_USER,
		map[string]any{"userName": userName, "userEmail": userEmail, "password": password},
		"",
	)
}

// LogIn mutation logIn (sin token): escribe porque rota el api_token del usuario
func (r *UsersWriterGraphqlRepository) LogIn(userName string, password string) (*graphqlrequester.ApiResponse, error) {
	return r.graphqlRequester.GetOperationResult(
		OPERATION_LOG_IN,
		MUTATION_LOG_IN,
		map[string]any{"userName": userName, "password": password},
		"",
	)
}
