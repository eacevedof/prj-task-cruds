package core.config;

import java.util.List;
import java.util.function.Supplier;

import modules.graphql.infrastructure.schema.InterfaceGraphqlSchemaProvider;

import modules.users.infrastructure.providers.UsersGraphqlSchemaProvider;
import modules.tasks.infrastructure.providers.TasksGraphqlSchemaProvider;

/**
 * El src/Core/config/graphql_schema.php de task-php: registro de los modulos en /graphql (lo lee GraphqlSchemaRegistry
 * solo cuando llega una peticion a /graphql). Cada modulo nuevo anade aqui su XxxGraphqlSchemaProvider, despues de
 * los modulos cuyos tipos extiende.
 */
public final class GraphqlSchema {

    private GraphqlSchema() {
    }

    public static List<Supplier<InterfaceGraphqlSchemaProvider>> getSchemaProviders() {
        return List.of(
            UsersGraphqlSchemaProvider::getInstance,
            TasksGraphqlSchemaProvider::getInstance
        );
    }
}
