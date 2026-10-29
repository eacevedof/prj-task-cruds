import { __ } from "#app/Core/config/global.ts";

import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import type { InterfaceException } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { GraphqlMessageEnum } from "#app/Modules/Graphql/Domain/Enums/GraphqlMessageEnum.ts";

/**
 * El registro de /graphql esta mal montado (src/Core/config/graphqlSchema.ts y los XxxGraphqlSchemaProvider): es un
 * fallo de programacion, no de la peticion, asi que se responde 500 y se loguea.
 */
export class GraphqlSchemaException extends Error implements InterfaceException {

    readonly #code: number;

    constructor(message: string, code: number) {
        super(message);
        this.name = "GraphqlSchemaException";
        this.#code = code;
    }

    /**
     * @throws GraphqlSchemaException
     */
    static fieldsAlreadyHaveResolver(typeName: string, fieldNames: string[], schemaProviderClass: string): never {
        throw new GraphqlSchemaException(
            __(GraphqlMessageEnum.GRAPHQL_FIELDS_X_X_ALREADY_HAVE_RESOLVER_X, typeName, fieldNames.join(", "), schemaProviderClass),
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
        );
    }

    /**
     * @throws GraphqlSchemaException
     */
    static fieldsWithoutResolver(typeName: string, fieldNames: string[]): never {
        throw new GraphqlSchemaException(
            __(GraphqlMessageEnum.GRAPHQL_FIELDS_X_X_HAVE_NO_RESOLVER, typeName, fieldNames.join(", ")),
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
        );
    }

    getCode(): number {
        return this.#code;
    }
}
