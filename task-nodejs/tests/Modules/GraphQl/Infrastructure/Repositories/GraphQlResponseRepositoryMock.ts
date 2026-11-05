import { GraphQlEnum } from "#app/Modules/GraphQl/Domain/Enums/GraphQlEnum.ts";
import type { HttpResponder } from "#app/Modules/Shared/Infrastructure/Components/HttpResponder/HttpResponder.ts";

type GraphQlResponseError = {
    message?: string;
    extensions?: {
        code?: number;
        errors?: string[];
    };
};

/**
 * Lectura de una respuesta GraphQL: {"errors": [{"message", "extensions": {"code", "errors"}}], "data": {...}}
 */
export class GraphQlResponseRepositoryMock {

    readonly #responseArray: { data?: Record<string, any>; errors?: GraphQlResponseError[] };

    constructor(responseContent: string) {
        this.#responseArray = JSON.parse(responseContent) ?? {};
    }

    static fromHttpResponse(httpResponse: HttpResponder): GraphQlResponseRepositoryMock {
        return new GraphQlResponseRepositoryMock(httpResponse.getBody());
    }

    getData(): Record<string, any> {
        return this.#responseArray[GraphQlEnum.KEY_DATA] ?? {};
    }

    getErrors(): GraphQlResponseError[] {
        return this.#responseArray[GraphQlEnum.KEY_ERRORS] ?? [];
    }

    /**
     * Codigo HTTP equivalente REST del primer error (0 si es un error de GraphQL, sin extensions).
     */
    getFirstErrorCode(): number {
        return Number(this.getErrors()[0]?.extensions?.[GraphQlEnum.EXTENSION_CODE] ?? 0);
    }

    getFirstErrorDetails(): string[] {
        return this.getErrors()[0]?.extensions?.[GraphQlEnum.EXTENSION_ERRORS] ?? [];
    }
}
