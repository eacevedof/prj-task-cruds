import { GraphQLError } from "graphql";

import { __ } from "#app/Core/config/global.ts";

import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { AbstractHttpController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractHttpController.ts";
import type { InterfaceException } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { GraphqlEnum } from "#app/Modules/Graphql/Domain/Enums/GraphqlEnum.ts";

/**
 * Base de los resolvers GraphQL (XxxGraphqlController, uno por caso de uso de Users y Tasks): son controllers (construyen el DTO, llaman
 * al servicio y capturan), pero en vez de un HttpResponder devuelven el objeto del ResultDto o lanzan un
 * GraphQLError. Los GraphQLError se crean sin originalError: su mensaje y sus extensions son lo que ve el cliente.
 */
export abstract class AbstractGraphqlController extends AbstractHttpController {

    /**
     * Excepcion controlada (de dominio o FieldsException), distinguida por su clase: su codigo 4xx va en
     * extensions.code y su mensaje en extensions.errors (una FieldsException ya trae en el mensaje el detalle por campo).
     */
    protected getGraphqlDomainError(ex: InterfaceException): GraphQLError {
        return new GraphQLError(ex.message, {
            extensions: {
                [GraphqlEnum.EXTENSION_CODE]: ex.getCode(),
                [GraphqlEnum.EXTENSION_ERRORS]: [ex.message],
            },
        });
    }

    /**
     * Cualquier otra cosa: 500, se loguea con el resolver que fallo y no filtra su mensaje.
     */
    protected getGraphqlUnexpectedError(ex: Error, logTitle: string): GraphQLError {
        this.logTrait.logException(ex, logTitle);
        this.mailTrait.sendMailOnThrowable(ex, logTitle);
        const unexpectedError = __(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED);
        return new GraphQLError(unexpectedError, {
            extensions: {
                [GraphqlEnum.EXTENSION_CODE]: ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
                [GraphqlEnum.EXTENSION_ERRORS]: [unexpectedError],
            },
        });
    }
}
