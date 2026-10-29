package modules.graphql.domain.enums;

/**
 * Mensajes propios del protocolo GraphQL (los de dominio siguen en el MessageEnum de cada modulo).
 */
public final class GraphqlMessageEnum {

    public static final String GRAPHQL_QUERY_IS_REQUIRED = "GraphQL query is required";
    public static final String GRAPHQL_FIELDS_X_X_ALREADY_HAVE_RESOLVER_X = "graphql: {0}.{1} already has a resolver ({2})";
    public static final String GRAPHQL_FIELDS_X_X_HAVE_NO_RESOLVER = "graphql: {0}.{1} has no resolver";

    private GraphqlMessageEnum() {
    }
}
