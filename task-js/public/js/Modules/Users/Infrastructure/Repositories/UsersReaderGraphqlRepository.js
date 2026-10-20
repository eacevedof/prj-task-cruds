import { GraphqlRequester } from "/js/Modules/Shared/Infrastructure/Components/GraphqlRequester.js";

const OPERATION_USERS = "users";
const QUERY_USERS = `
query Users {
    users { user_uuid user_name user_email }
}`;

export class UsersReaderGraphqlRepository {

    #graphqlRequester;

    constructor() {
        this.#graphqlRequester = GraphqlRequester.getInstance();
    }

    /**
     * @returns {UsersReaderGraphqlRepository}
     */
    static getInstance() {
        return new UsersReaderGraphqlRepository();
    }

    /**
     * query users: con un token, la lista solo trae a su usuario.
     * @param {string} apiToken
     */
    async getUsers(apiToken) {
        return this.#graphqlRequester.getOperationResult(OPERATION_USERS, QUERY_USERS, {}, apiToken);
    }
}
