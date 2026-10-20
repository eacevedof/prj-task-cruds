import { GraphqlRequester } from "/js/Modules/Shared/Infrastructure/Components/GraphqlRequester.js";

const OPERATION_CREATE_USER = "createUser";
const MUTATION_CREATE_USER = `
mutation CreateUser($userName: String!, $userEmail: String!, $password: String!) {
    createUser(user_name: $userName, user_email: $userEmail, password: $password) { user_uuid user_name user_email api_token }
}`;

const OPERATION_LOG_IN = "logIn";
const MUTATION_LOG_IN = `
mutation LogIn($userName: String!, $password: String!) {
    logIn(user_name: $userName, password: $password) { user_uuid user_name user_email api_token }
}`;

export class UsersWriterGraphqlRepository {

    #graphqlRequester;

    constructor() {
        this.#graphqlRequester = GraphqlRequester.getInstance();
    }

    /**
     * @returns {UsersWriterGraphqlRepository}
     */
    static getInstance() {
        return new UsersWriterGraphqlRepository();
    }

    /**
     * mutation createUser (alta abierta, sin token)
     */
    async createUser(userName, userEmail, password) {
        return this.#graphqlRequester.getOperationResult(
            OPERATION_CREATE_USER,
            MUTATION_CREATE_USER,
            { userName, userEmail, password }
        );
    }

    /**
     * mutation logIn (sin token): escribe porque rota el api_token del usuario
     */
    async logIn(userName, password) {
        return this.#graphqlRequester.getOperationResult(
            OPERATION_LOG_IN,
            MUTATION_LOG_IN,
            { userName, password }
        );
    }
}
