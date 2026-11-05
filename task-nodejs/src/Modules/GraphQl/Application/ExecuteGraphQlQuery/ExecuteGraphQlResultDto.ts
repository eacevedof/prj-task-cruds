import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

import { GraphQlEnum } from "#app/Modules/GraphQl/Domain/Enums/GraphQlEnum.ts";

/**
 * Respuesta GraphQL {errors?, data?}: las claves solo van si traen algo y en el orden de webonyx (errors primero).
 */
export class ExecuteGraphQlResultDto {

    readonly #errors: unknown[];
    readonly #data: Primitives | null;

    constructor(errors: unknown[], data: Primitives | null) {
        this.#errors = errors;
        this.#data = data;
    }

    /**
     * @param graphqlResult el ExecutionResult de graphql-js ya serializado ({errors?, data?}), como el toArray() de webonyx
     */
    static fromPrimitives(graphqlResult: Primitives): ExecuteGraphQlResultDto {
        const errors = graphqlResult[GraphQlEnum.KEY_ERRORS];
        const data = graphqlResult[GraphQlEnum.KEY_DATA];
        return new ExecuteGraphQlResultDto(
            Array.isArray(errors) ? errors : [],
            data !== null && typeof data === "object" ? data as Primitives : null,
        );
    }

    /**
     * Error antes de ejecutar (peticion sin query, fallo inesperado): sin data, como lo daria GraphQL.
     */
    static fromErrorMessage(message: string): ExecuteGraphQlResultDto {
        return new ExecuteGraphQlResultDto([{ [GraphQlEnum.KEY_MESSAGE]: message }], null);
    }

    toArray(): Primitives {
        const graphqlResult: Primitives = {};
        if (this.#errors.length) graphqlResult[GraphQlEnum.KEY_ERRORS] = this.#errors;
        if (this.#data !== null) graphqlResult[GraphQlEnum.KEY_DATA] = this.#data;
        return graphqlResult;
    }
}
