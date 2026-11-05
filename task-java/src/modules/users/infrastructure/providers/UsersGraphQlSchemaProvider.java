package modules.users.infrastructure.providers;

import java.util.LinkedHashMap;
import java.util.Map;
import java.util.function.BiFunction;

import modules.graphql.domain.enums.GraphQlEnum;
import modules.graphql.infrastructure.providers.GraphQlSchemaProvidersRegistry;
import modules.graphql.infrastructure.providers.InterfaceGraphQlSchemaProvider;

import modules.users.infrastructure.controllers.CreateUserGraphQlController;
import modules.users.infrastructure.controllers.DeleteUserGraphQlController;
import modules.users.infrastructure.controllers.GetUserGraphQlController;
import modules.users.infrastructure.controllers.ListUsersGraphQlController;
import modules.users.infrastructure.controllers.LogInUserGraphQlController;
import modules.users.infrastructure.controllers.RegenerateUserApiTokenGraphQlController;
import modules.users.infrastructure.controllers.UpdateUserGraphQlController;

/**
 * Parte de users en /graphql: users.graphql (define Query, Mutation y User) y sus resolvers. El resolver se crea
 * solo si la consulta pide su campo.
 */
public final class UsersGraphQlSchemaProvider implements InterfaceGraphQlSchemaProvider {

    // tipo que define users.graphql y extienden otros modulos (Tasks le anade tasks)
    public static final String TYPE_USER = "User";

    public static UsersGraphQlSchemaProvider getInstance() {
        return new UsersGraphQlSchemaProvider();
    }

    @Override
    public String getGraphQlFileContent() {
        return GraphQlSchemaProvidersRegistry.getResourceContent(UsersGraphQlSchemaProvider.class, "users.graphql");
    }

    @Override
    public Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> getControllerResolvers() {
        Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> controllerResolvers = new LinkedHashMap<>();
        controllerResolvers.put(GraphQlEnum.TYPE_QUERY, getQueryControllers());
        controllerResolvers.put(GraphQlEnum.TYPE_MUTATION, getMutationControllers());
        return controllerResolvers;
    }

    private Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> getQueryControllers() {
        Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> queryControllers = new LinkedHashMap<>();
        queryControllers.put("users", (gqlPayload, parent) -> ListUsersGraphQlController.getInstance().invoke());
        queryControllers.put("user", (gqlPayload, parent) -> GetUserGraphQlController.getInstance().invoke(gqlPayload));
        return queryControllers;
    }

    private Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> getMutationControllers() {
        Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> mutationControllers = new LinkedHashMap<>();
        mutationControllers.put("createUser", (gqlPayload, parent) -> CreateUserGraphQlController.getInstance().invoke(gqlPayload));
        mutationControllers.put("logIn", (gqlPayload, parent) -> LogInUserGraphQlController.getInstance().invoke(gqlPayload));
        mutationControllers.put("updateUser", (gqlPayload, parent) -> UpdateUserGraphQlController.getInstance().invoke(gqlPayload));
        mutationControllers.put("deleteUser", (gqlPayload, parent) -> DeleteUserGraphQlController.getInstance().invoke(gqlPayload));
        mutationControllers.put("regenerateApiToken", (gqlPayload, parent) -> RegenerateUserApiTokenGraphQlController.getInstance().invoke(gqlPayload));
        return mutationControllers;
    }
}
