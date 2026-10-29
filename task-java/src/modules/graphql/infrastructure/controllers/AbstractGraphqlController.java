package modules.graphql.infrastructure.controllers;

import java.util.List;

import graphql.GraphqlErrorException;

import modules.shared.application.Primitives;
import modules.shared.domain.enums.ExceptionEnum;
import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.controllers.AbstractHttpController;
import modules.shared.infrastructure.exceptions.InterfaceException;

import modules.graphql.domain.enums.GraphqlEnum;

import static core.config.Global.__;

/**
 * Base de los resolvers GraphQL de cada modulo (XxxGraphqlController): son controllers (construyen el DTO, llaman
 * al servicio y capturan), pero en vez de un HttpResponder devuelven el mapa del ResultDto o lanzan un
 * GraphqlErrorException. Su mensaje y sus extensions son lo que ve el cliente (GraphqlExecutor le pone path y locations).
 */
public abstract class AbstractGraphqlController extends AbstractHttpController {

    /**
     * Excepcion controlada (de dominio o FieldsException), distinguida por su clase: su codigo 4xx va en
     * extensions.code y su mensaje en extensions.errors (una FieldsException ya trae en el mensaje el detalle por campo).
     */
    protected GraphqlErrorException getGraphqlDomainError(InterfaceException ex) {
        return GraphqlErrorException.newErrorException()
            .message(ex.getMessage())
            .extensions(Primitives.of(
                GraphqlEnum.EXTENSION_CODE, ex.getCode(),
                GraphqlEnum.EXTENSION_ERRORS, List.of(ex.getMessage())
            ))
            .build();
    }

    /**
     * Cualquier otra cosa: 500, se loguea con el resolver que fallo y no filtra su mensaje.
     */
    protected GraphqlErrorException getGraphqlUnexpectedError(Throwable ex, String logTitle) {
        logTrait.logException(ex, logTitle);
        mailTrait.sendMailOnThrowable(ex, logTitle);
        String unexpectedError = __(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED);
        return GraphqlErrorException.newErrorException()
            .message(unexpectedError)
            .extensions(Primitives.of(
                GraphqlEnum.EXTENSION_CODE, ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
                GraphqlEnum.EXTENSION_ERRORS, List.of(unexpectedError)
            ))
            .build();
    }
}
