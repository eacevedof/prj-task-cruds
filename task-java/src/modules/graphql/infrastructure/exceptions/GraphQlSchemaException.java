package modules.graphql.infrastructure.exceptions;

import java.util.List;

import modules.shared.domain.enums.ExceptionEnum;

import modules.graphql.domain.enums.GraphQlMessageEnum;

import static core.config.Global.__;

/**
 * El registro de /graphql esta mal montado: es un fallo del programador, no de la peticion. Salta al montar el
 * schema, que en Java se monta una vez por proceso (en la primera peticion a /graphql); si falla no se guarda nada, asi
 * que rompe TODAS las llamadas a /graphql (aunque la query no toque el campo afectado). La captura el catch Exception
 * de ExecuteGraphQlQueryController: el mensaje va al log y el cliente solo recibe un 500 con el texto generico, sin
 * data. No es una InterfaceException (esas son 4xx). Se lanza en el sitio: throw GraphQlSchemaException.fieldsWithoutResolver(...).
 */
public final class GraphQlSchemaException extends RuntimeException {

    private final int code;

    private GraphQlSchemaException(String message, int code) {
        super(message);
        this.code = code;
    }

    /**
     * Dos providers registran resolver para el mismo tipo.campo: el segundo pisaria al primero sin avisar.
     * La lanza GraphQlSchemaProvidersRegistry.getAllMergedResolversByType() al unir los providers en el orden de
     * getAllModulesProviders().
     *
     * Ejemplo: TasksGraphQlSchemaProvider anade por error "user" en Query, que ya es de Users
     *   typeName            "Query"
     *   fieldNames          ["user"]  (o varios: ["tasks", "task"] -> "Query.tasks, task")
     *   schemaProviderClass "modules.tasks.infrastructure.providers.TasksGraphQlSchemaProvider"
     *                       (el que llega SEGUNDO y choca, no el que ya tenia el campo)
     *   mensaje: graphql: Query.user already has a resolver (modules.tasks...TasksGraphQlSchemaProvider)
     *
     * @param typeName tipo del schema: "Query", "Mutation" o uno de negocio como "User"
     * @param fieldNames campos repetidos de ese tipo
     * @param schemaProviderClass nombre completo de la clase del provider que intenta registrarlos de nuevo
     */
    public static GraphQlSchemaException fieldsAlreadyHaveResolver(
        String typeName,
        List<String> fieldNames,
        String schemaProviderClass
    ) {
        return new GraphQlSchemaException(
            __(
                GraphQlMessageEnum.GRAPHQL_FIELDS_X_X_ALREADY_HAVE_RESOLVER_X,
                typeName,
                String.join(", ", fieldNames),
                schemaProviderClass
            ),
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
        );
    }

    /**
     * Un campo de Query o Mutation esta en el .graphql pero ningun provider le da resolver: sin esto devolveria null
     * sin avisar. La lanza ExecuteGraphQlQueryService.failIfRootFieldWithoutResolver() tras montar el schema.
     * Solo se comprueban los tipos raiz: un campo de un tipo de negocio sin resolver (p. ej. User.tasks) no salta
     * aqui, lo resuelve el resolver por defecto leyendo del mapa padre (y daria null).
     *
     * Ejemplo: se anade "archiveTask(task_uuid: ID!): Task" al "extend type Mutation" de tasks.graphql y se olvida
     * el resolver en TasksGraphQlSchemaProvider.getMutationControllers()
     *   typeName   "Mutation"
     *   fieldNames ["archiveTask"]
     *   mensaje: graphql: Mutation.archiveTask has no resolver
     *
     * @param typeName solo "Query" o "Mutation" (GraphQlEnum.TYPE_QUERY / TYPE_MUTATION)
     * @param fieldNames campos declarados en el schema sin resolver registrado
     */
    public static GraphQlSchemaException fieldsWithoutResolver(
        String typeName,
        List<String> fieldNames
    ) {
        return new GraphQlSchemaException(
            __(
                GraphQlMessageEnum.GRAPHQL_FIELDS_X_X_HAVE_NO_RESOLVER,
                typeName,
                String.join(", ", fieldNames)
            ),
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
        );
    }

    public int getCode() {
        return code;
    }
}
