package modules.graphql.infrastructure.exceptions;

import java.util.List;

import modules.shared.domain.enums.ExceptionEnum;

import modules.graphql.domain.enums.GraphqlMessageEnum;

import static core.config.Global.__;

/**
 * El registro de /graphql esta mal montado (src/core/config/GraphqlSchema.java y los XxxGraphqlSchemaProvider): es un
 * fallo de programacion, no de la peticion, asi que se responde 500 y se loguea. No es una InterfaceException (esas
 * son 4xx). Se lanza en el sitio: throw GraphqlSchemaException.fieldsWithoutResolver(...).
 */
public final class GraphqlSchemaException extends RuntimeException {

    private final int code;

    private GraphqlSchemaException(String message, int code) {
        super(message);
        this.code = code;
    }

    public static GraphqlSchemaException fieldsAlreadyHaveResolver(String typeName, List<String> fieldNames, String schemaProviderClass) {
        return new GraphqlSchemaException(
            __(GraphqlMessageEnum.GRAPHQL_FIELDS_X_X_ALREADY_HAVE_RESOLVER_X, typeName, String.join(", ", fieldNames), schemaProviderClass),
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
        );
    }

    public static GraphqlSchemaException fieldsWithoutResolver(String typeName, List<String> fieldNames) {
        return new GraphqlSchemaException(
            __(GraphqlMessageEnum.GRAPHQL_FIELDS_X_X_HAVE_NO_RESOLVER, typeName, String.join(", ", fieldNames)),
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
        );
    }

    public int getCode() {
        return code;
    }
}
