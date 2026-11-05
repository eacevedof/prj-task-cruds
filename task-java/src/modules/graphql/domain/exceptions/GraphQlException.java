package modules.graphql.domain.exceptions;

import java.util.List;
import java.util.Map;

import graphql.ErrorClassification;
import graphql.ErrorType;
import graphql.GraphQLError;
import graphql.language.SourceLocation;

import modules.shared.application.Primitives;
import modules.shared.domain.enums.ExceptionEnum;
import modules.shared.infrastructure.exceptions.InterfaceException;

import modules.graphql.domain.enums.GraphQlEnum;

/**
 * Lo que lanza un resolver para que su mensaje llegue al cliente: el ClientAware + ProvidesExtensions de webonyx en
 * graphql-java es un GraphQLError. El DataFetcherExceptionHandler de ExecuteGraphQlQueryService deja su mensaje y sus
 * extensions tal cual (code en extensions.code y su mensaje en extensions.errors) y le pone path y locations.
 * Las factorias devuelven la excepcion y se lanza en el sitio (como el resto de excepciones de la replica, el flujo
 * de Java tiene que ver el throw): throw GraphQlException.internalServerError(...).
 */
public final class GraphQlException extends RuntimeException implements GraphQLError {

    private final int code;

    private GraphQlException(String message, int code) {
        super(message);
        this.code = code;
    }

    public int getCode() {
        return code;
    }

    @Override
    public Map<String, Object> getExtensions() {
        return Primitives.of(
            GraphQlEnum.EXTENSION_CODE, code,
            GraphQlEnum.EXTENSION_ERRORS, List.of(getMessage())
        );
    }

    @Override
    public List<SourceLocation> getLocations() {
        return null;
    }

    @Override
    public ErrorClassification getErrorType() {
        return ErrorType.DataFetchingException;
    }

    /**
     * Excepcion controlada (de dominio o FieldsException): su codigo 4xx y su mensaje pasan tal cual al cliente.
     */
    public static GraphQlException clientAwareExceptionForWebonyx(InterfaceException ex) {
        return new GraphQlException(ex.getMessage(), ex.getCode());
    }

    /**
     * Fallo no controlado: 500 con un mensaje generico, el original no sale (el controller ya lo ha logueado).
     */
    public static GraphQlException internalServerError(String message) {
        return new GraphQlException(message, ExceptionEnum.CODE_INTERNAL_SERVER_ERROR);
    }
}
