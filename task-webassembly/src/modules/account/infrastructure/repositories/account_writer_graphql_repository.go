package repositories

import "task-webassembly/src/modules/shared/infrastructure/components/graphqlrequester"

const (
	OPERATION_UPDATE_USER = "updateUser"
	MUTATION_UPDATE_USER  = `
mutation UpdateUser($userUuid: ID!, $userName: String!, $userEmail: String!) {
    updateUser(user_uuid: $userUuid, user_name: $userName, user_email: $userEmail) { user_uuid user_name user_email }
}`

	OPERATION_REGENERATE_API_TOKEN = "regenerateApiToken"
	MUTATION_REGENERATE_API_TOKEN  = `
mutation RegenerateApiToken($userUuid: ID!) {
    regenerateApiToken(user_uuid: $userUuid) { user_uuid api_token }
}`

	OPERATION_DELETE_USER = "deleteUser"
	MUTATION_DELETE_USER  = `
mutation DeleteUser($userUuid: ID!) {
    deleteUser(user_uuid: $userUuid) { user_uuid affected }
}`
)

// AccountWriterGraphqlRepository la cuenta es el usuario del token: escribe con las mutations de users.
type AccountWriterGraphqlRepository struct {
	graphqlRequester *graphqlrequester.GraphqlRequester
}

func AccountWriterGraphqlRepositoryGetInstance() *AccountWriterGraphqlRepository {
	return &AccountWriterGraphqlRepository{
		graphqlRequester: graphqlrequester.GetInstance(),
	}
}

func (r *AccountWriterGraphqlRepository) UpdateUserByUserUuid(
	userUuid string, userName string, userEmail string, apiToken string,
) (*graphqlrequester.ApiResponse, error) {
	return r.graphqlRequester.GetOperationResult(
		OPERATION_UPDATE_USER,
		MUTATION_UPDATE_USER,
		map[string]any{"userUuid": userUuid, "userName": userName, "userEmail": userEmail},
		apiToken,
	)
}

func (r *AccountWriterGraphqlRepository) RegenerateApiTokenByUserUuid(userUuid string, apiToken string) (*graphqlrequester.ApiResponse, error) {
	return r.graphqlRequester.GetOperationResult(
		OPERATION_REGENERATE_API_TOKEN,
		MUTATION_REGENERATE_API_TOKEN,
		map[string]any{"userUuid": userUuid},
		apiToken,
	)
}

func (r *AccountWriterGraphqlRepository) DeleteUserByUserUuid(userUuid string, apiToken string) (*graphqlrequester.ApiResponse, error) {
	return r.graphqlRequester.GetOperationResult(
		OPERATION_DELETE_USER,
		MUTATION_DELETE_USER,
		map[string]any{"userUuid": userUuid},
		apiToken,
	)
}
