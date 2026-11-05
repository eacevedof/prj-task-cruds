import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import type { InterfaceException } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { GraphQlEnum } from "#app/Modules/GraphQl/Domain/Enums/GraphQlEnum.ts";

/**
 * Lo que lanza un resolver para que su mensaje llegue al cliente: graphql-js la envuelve en un GraphQLError
 * (locatedError) con su mismo mensaje y, como la guarda en originalError, copia sus extensions (el ProvidesExtensions
 * de webonyx): su codigo en extensions.code y su mensaje en extensions.errors. graphql-js no oculta mensajes, asi que
 * no hace falta el ClientAware de webonyx.
 */
export class GraphQlException extends Error implements InterfaceException {

    readonly #code: number;

    constructor(message: string, code: number) {
        super(message);
        this.name = "GraphQlException";
        this.#code = code;
    }

    /**
     * Excepcion controlada (de dominio o FieldsException): su codigo 4xx y su mensaje pasan tal cual al cliente.
     * @throws GraphQlException
     */
    static clientAwareExceptionForWebonyx(ex: InterfaceException): never {
        throw new GraphQlException(ex.message, ex.getCode());
    }

    /**
     * Fallo no controlado: 500 con un mensaje generico, el original no sale (el controller ya lo ha logueado).
     * @throws GraphQlException
     */
    static internalServerError(message: string): never {
        throw new GraphQlException(message, ExceptionEnum.CODE_INTERNAL_SERVER_ERROR);
    }

    getCode(): number {
        return this.#code;
    }

    getExtensions(): Primitives {
        return {
            [GraphQlEnum.EXTENSION_CODE]: this.#code,
            [GraphQlEnum.EXTENSION_ERRORS]: [this.message],
        };
    }

    /**
     * Lo que lee graphql-js del originalError al montar el GraphQLError de la respuesta.
     */
    get extensions(): Primitives {
        return this.getExtensions();
    }
}
