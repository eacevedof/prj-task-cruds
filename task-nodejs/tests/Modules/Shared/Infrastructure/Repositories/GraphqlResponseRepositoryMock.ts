import { GraphqlEnum } from "#app/Modules/Shared/Domain/Enums/GraphqlEnum.ts";
import type { HttpResponder } from "#app/Modules/Shared/Infrastructure/Components/HttpResponder/HttpResponder.ts";

type GraphqlResponseError = {
    message?: string;
    extensions?: {
        code?: number;
        errors?: string[];
    };
};

/**
 * Lectura de una respuesta GraphQL: {"errors": [{"message", "extensions": {"code", "errors"}}], "data": {...}}
 */
export class GraphqlResponseRepositoryMock {

    readonly #responseArray: { data?: Record<string, any>; errors?: GraphqlResponseError[] };

    constructor(responseContent: string) {
        this.#responseArray = JSON.parse(responseContent) ?? {};
    }

    static fromHttpResponse(httpResponse: HttpResponder): GraphqlResponseRepositoryMock {
        return new GraphqlResponseRepositoryMock(httpResponse.getBody());
    }

    getData(): Record<string, any> {
        return this.#responseArray[GraphqlEnum.KEY_DATA] ?? {};
    }

    getErrors(): GraphqlResponseError[] {
        return this.#responseArray[GraphqlEnum.KEY_ERRORS] ?? [];
    }

    /**
     * Codigo HTTP equivalente REST del primer error (0 si es un error de GraphQL, sin extensions).
     */
    getFirstErrorCode(): number {
        return Number(this.getErrors()[0]?.extensions?.[GraphqlEnum.EXTENSION_CODE] ?? 0);
    }

    getFirstErrorDetails(): string[] {
        return this.getErrors()[0]?.extensions?.[GraphqlEnum.EXTENSION_ERRORS] ?? [];
    }
}
