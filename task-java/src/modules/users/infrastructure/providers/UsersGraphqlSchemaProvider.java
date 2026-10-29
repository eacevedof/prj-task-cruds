package modules.users.infrastructure.providers;

import java.util.LinkedHashMap;
import java.util.Map;
import java.util.function.BiFunction;

import modules.graphql.domain.enums.GraphqlEnum;
import modules.graphql.infrastructure.schema.GraphqlSchemaRegistry;
import modules.graphql.infrastructure.schema.InterfaceGraphqlSchemaProvider;

import modules.users.infrastructure.controllers.CreateUserGraphqlController;
import modules.users.infrastructure.controllers.DeleteUserGraphqlController;
import modules.users.infrastructure.controllers.GetUserGraphqlController;
import modules.users.infrastructure.controllers.ListUsersGraphqlController;
import modules.users.infrastructure.controllers.LogInUserGraphqlController;
import modules.users.infrastructure.controllers.RegenerateUserApiTokenGraphqlController;
import modules.users.infrastructure.controllers.UpdateUserGraphqlController;

/**
 * Parte de users en /graphql: users.graphql (define Query, Mutation y User) y sus resolvers. El resolver se crea
 * solo si la consulta pide su campo.
 */
public final class UsersGraphqlSchemaProvider implements InterfaceGraphqlSchemaProvider {

    public static UsersGraphqlSchemaProvider getInstance() {
        return new UsersGraphqlSchemaProvider();
    }

    @Override
    public String getSdl() {
        return GraphqlSchemaRegistry.getResourceContent(UsersGraphqlSchemaProvider.class, "users.graphql");
    }

    @Override
    public Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> getResolversByType() {
        Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> queryResolvers = new LinkedHashMap<>();
        queryResolvers.put("users", (args, parent) -> ListUsersGraphqlController.getInstance().invoke());
        queryResolvers.put("user", (args, parent) -> GetUserGraphqlController.getInstance().invoke(args));

        Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> mutationResolvers = new LinkedHashMap<>();
        mutationResolvers.put("createUser", (args, parent) -> CreateUserGraphqlController.getInstance().invoke(args));
        mutationResolvers.put("logIn", (args, parent) -> LogInUserGraphqlController.getInstance().invoke(args));
        mutationResolvers.put("updateUser", (args, parent) -> UpdateUserGraphqlController.getInstance().invoke(args));
        mutationResolvers.put("deleteUser", (args, parent) -> DeleteUserGraphqlController.getInstance().invoke(args));
        mutationResolvers.put("regenerateApiToken", (args, parent) -> RegenerateUserApiTokenGraphqlController.getInstance().invoke(args));

        Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> resolversByType = new LinkedHashMap<>();
        resolversByType.put(GraphqlEnum.TYPE_QUERY, queryResolvers);
        resolversByType.put(GraphqlEnum.TYPE_MUTATION, mutationResolvers);
        return resolversByType;
    }
}
