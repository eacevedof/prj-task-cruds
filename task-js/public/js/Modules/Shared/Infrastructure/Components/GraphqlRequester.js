const GRAPHQL_PATH = "/graphql";
const CODE_OK = 200;
// error de GraphQL sin extensions.code (consulta mal formada, campo inexistente): es un fallo del cliente
const CODE_GRAPHQL_ERROR = 400;

/**
 * POST /graphql de la API con {query, variables} y el Bearer. Normaliza la respuesta a la misma forma que
 * usan los servicios: {code, status, errors, data}, donde data es el campo de la operacion pedida.
 * GraphQL responde 200 tambien con errores: el codigo real viaja en errors[0].extensions.code.
 * No captura: fallo de red o una respuesta que no es JSON propagan. Una sola instancia: la url se fija al arrancar.
 */
export class GraphqlRequester {

    static #instance = null;

    #apiBaseUrl = "";

    /**
     * @returns {GraphqlRequester}
     */
    static getInstance() {
        GraphqlRequester.#instance ??= new GraphqlRequester();
        return GraphqlRequester.#instance;
    }

    /**
     * @param {string} apiBaseUrl
     */
    setApiBaseUrl(apiBaseUrl) {
        this.#apiBaseUrl = String(apiBaseUrl ?? "").trim().replace(/\/+$/, "");
    }

    /**
     * @param {string} operationName campo raiz de la query/mutation cuyo resultado se devuelve en data
     * @param {string} query
     * @param {Object} variables
     * @param {string} apiToken "" para operaciones publicas (createUser)
     * @returns {Promise<{code: number, status: boolean, errors: string[], data: *}>}
     */
    async getOperationResult(operationName, query, variables = {}, apiToken = "") {
        const headers = { "Accept": "application/json", "Content-Type": "application/json" };
        if (apiToken) {
            headers["Authorization"] = `Bearer ${apiToken}`;
        }

        const response = await fetch(`${this.#apiBaseUrl}${GRAPHQL_PATH}`, {
            method: "POST",
            headers,
            body: JSON.stringify({ query, variables }),
        });
        const json = await response.json();

        const firstError = json.errors?.[0];
        if (!firstError) {
            return { code: CODE_OK, status: true, errors: [], data: json.data?.[operationName] ?? null };
        }
        return {
            code: Number(firstError.extensions?.code ?? (response.ok ? CODE_GRAPHQL_ERROR : response.status)),
            status: false,
            errors: firstError.extensions?.errors ?? json.errors.map((error) => error.message),
            data: null,
        };
    }
}
