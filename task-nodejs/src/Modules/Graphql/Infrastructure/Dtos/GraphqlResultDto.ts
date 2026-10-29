import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

import { GraphqlEnum } from "#app/Modules/Graphql/Domain/Enums/GraphqlEnum.ts";

/**
 * Respuesta GraphQL {errors?, data?}: las claves solo van si traen algo y en el orden de webonyx (errors primero).
 */
export class GraphqlResultDto {

    readonly #errors: unknown[];
    readonly #data: Primitives | null;

    constructor(errors: unknown[], data: Primitives | null) {
        this.#errors = errors;
        this.#data = data;
    }

    /**
     * @param graphqlResult el ExecutionResult de graphql-js ya serializado ({errors?, data?}), como el toArray() de webonyx
     */
    static fromPrimitives(graphqlResult: Primitives): GraphqlResultDto {
        const errors = graphqlResult[GraphqlEnum.KEY_ERRORS];
        const data = graphqlResult[GraphqlEnum.KEY_DATA];
        return new GraphqlResultDto(
            Array.isArray(errors) ? errors : [],
            data !== null && typeof data === "object" ? data as Primitives : null,
        );
    }

    /**
     * Error antes de ejecutar (peticion sin query, fallo inesperado): sin data, como lo daria GraphQL.
     */
    static fromErrorMessage(message: string): GraphqlResultDto {
        return new GraphqlResultDto([{ [GraphqlEnum.KEY_MESSAGE]: message }], null);
    }

    toArray(): Primitives {
        const graphqlResult: Primitives = {};
        if (this.#errors.length) graphqlResult[GraphqlEnum.KEY_ERRORS] = this.#errors;
        if (this.#data !== null) graphqlResult[GraphqlEnum.KEY_DATA] = this.#data;
        return graphqlResult;
    }
}
