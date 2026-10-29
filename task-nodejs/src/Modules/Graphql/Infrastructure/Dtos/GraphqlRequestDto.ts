import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

import { GraphqlEnum } from "#app/Modules/Graphql/Domain/Enums/GraphqlEnum.ts";

/**
 * Body de POST /graphql: {query, variables?, operationName?}.
 */
export class GraphqlRequestDto {

    readonly #query: string;
    readonly #variables: Primitives;
    readonly #operationName: string | null;

    constructor(query: string, variables: Primitives, operationName: string | null) {
        this.#query = query;
        this.#variables = variables;
        this.#operationName = operationName;
    }

    static fromPrimitives(primitives: Primitives): GraphqlRequestDto {
        return new GraphqlRequestDto(
            GraphqlRequestDto.#getScalarAsString(primitives[GraphqlEnum.KEY_QUERY] ?? ""),
            GraphqlRequestDto.#getVariablesAsObject(primitives[GraphqlEnum.KEY_VARIABLES] ?? {}),
            GraphqlRequestDto.#getScalarAsString(primitives[GraphqlEnum.KEY_OPERATION_NAME] ?? "") || null,
        );
    }

    /**
     * Un array/objeto donde va un string cuenta como vacio (como HttpRequester.getPostString), no como "[object Object]".
     * is_scalar() ? trim((string) $value) : "": false es "" y true es "1", como en PHP.
     */
    static #getScalarAsString(value: unknown): string {
        if (typeof value === "string") return value.trim();
        if (typeof value === "number") return String(value);
        if (typeof value === "boolean") return value ? "1" : "";
        return "";
    }

    /**
     * "variables" puede llegar como objeto JSON o, en algunos clientes, como string JSON.
     */
    static #getVariablesAsObject(variables: unknown): Primitives {
        if (typeof variables === "string") {
            try {
                variables = JSON.parse(variables);
            }
            catch {
                variables = {};
            }
        }
        return variables !== null && typeof variables === "object" && !Array.isArray(variables)
            ? variables as Primitives
            : {};
    }

    getQuery(): string {
        return this.#query;
    }

    getVariables(): Primitives {
        return this.#variables;
    }

    getOperationName(): string | null {
        return this.#operationName;
    }
}
