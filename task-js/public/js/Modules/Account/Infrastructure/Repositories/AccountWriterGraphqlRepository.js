import { GraphqlRequester } from "/js/Modules/Shared/Infrastructure/Components/GraphqlRequester.js";

const OPERATION_UPDATE_USER = "updateUser";
const MUTATION_UPDATE_USER = `
mutation UpdateUser($userUuid: ID!, $userName: String!, $userEmail: String!) {
    updateUser(user_uuid: $userUuid, user_name: $userName, user_email: $userEmail) { user_uuid user_name user_email }
}`;

const OPERATION_REGENERATE_API_TOKEN = "regenerateApiToken";
const MUTATION_REGENERATE_API_TOKEN = `
mutation RegenerateApiToken($userUuid: ID!) {
    regenerateApiToken(user_uuid: $userUuid) { user_uuid api_token }
}`;

const OPERATION_DELETE_USER = "deleteUser";
const MUTATION_DELETE_USER = `
mutation DeleteUser($userUuid: ID!) {
    deleteUser(user_uuid: $userUuid) { user_uuid affected }
}`;

/**
 * La cuenta es el usuario del token: escribe con las mutations de users.
 */
export class AccountWriterGraphqlRepository {

    #graphqlRequester;

    constructor() {
        this.#graphqlRequester = GraphqlRequester.getInstance();
    }

    /**
     * @returns {AccountWriterGraphqlRepository}
     */
    static getInstance() {
        return new AccountWriterGraphqlRepository();
    }

    async updateUserByUserUuid(userUuid, userName, userEmail, apiToken) {
        return this.#graphqlRequester.getOperationResult(
            OPERATION_UPDATE_USER,
            MUTATION_UPDATE_USER,
            { userUuid, userName, userEmail },
            apiToken
        );
    }

    async regenerateApiTokenByUserUuid(userUuid, apiToken) {
        return this.#graphqlRequester.getOperationResult(
            OPERATION_REGENERATE_API_TOKEN,
            MUTATION_REGENERATE_API_TOKEN,
            { userUuid },
            apiToken
        );
    }

    async deleteUserByUserUuid(userUuid, apiToken) {
        return this.#graphqlRequester.getOperationResult(
            OPERATION_DELETE_USER,
            MUTATION_DELETE_USER,
            { userUuid },
            apiToken
        );
    }
}
