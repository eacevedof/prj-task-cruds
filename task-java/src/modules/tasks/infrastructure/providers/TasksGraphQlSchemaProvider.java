package modules.tasks.infrastructure.providers;

import java.util.LinkedHashMap;
import java.util.Map;
import java.util.function.BiFunction;

import modules.shared.application.Primitives;

import modules.graphql.domain.enums.GraphQlEnum;
import modules.graphql.infrastructure.providers.GraphQlSchemaProvidersRegistry;
import modules.graphql.infrastructure.providers.InterfaceGraphQlSchemaProvider;

import modules.users.domain.enums.UsersKeyEnum;
import modules.users.infrastructure.providers.UsersGraphQlSchemaProvider;

import modules.tasks.infrastructure.controllers.CreateTaskGraphQlController;
import modules.tasks.infrastructure.controllers.DeleteTaskGraphQlController;
import modules.tasks.infrastructure.controllers.GetTaskGraphQlController;
import modules.tasks.infrastructure.controllers.SearchTasksByUserGraphQlController;
import modules.tasks.infrastructure.controllers.SearchTasksGraphQlController;
import modules.tasks.infrastructure.controllers.UpdateTaskGraphQlController;

/**
 * Parte de tasks en /graphql: tasks.graphql (extiende Query, Mutation y User de users) y sus resolvers, incluido
 * User.tasks, que cuelga de User pero es de tasks. Va despues de UsersGraphQlSchemaProvider en el registro.
 */
public final class TasksGraphQlSchemaProvider implements InterfaceGraphQlSchemaProvider {

    public static TasksGraphQlSchemaProvider getInstance() {
        return new TasksGraphQlSchemaProvider();
    }

    @Override
    public String getGraphQlFileContent() {
        return GraphQlSchemaProvidersRegistry.getResourceContent(TasksGraphQlSchemaProvider.class, "tasks.graphql");
    }

    @Override
    public Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> getControllerResolvers() {
        Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> controllerResolvers = new LinkedHashMap<>();
        controllerResolvers.put(GraphQlEnum.TYPE_QUERY, getQueryControllers());

        controllerResolvers.put(GraphQlEnum.TYPE_MUTATION, getMutationControllers());

        // Las tareas de un usuario.
        // User.tasks: solo ejemplo de extender un tipo de otro modulo, no lo consume ningun front (ver tasks.graphql)
        Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> userControllers = new LinkedHashMap<>();
        userControllers.put("tasks", (gqlPayload, user) -> SearchTasksByUserGraphQlController.getInstance().invoke(
            Primitives.getTrimmedString(user.get(UsersKeyEnum.USER_UUID)),
            gqlPayload
        ));
        controllerResolvers.put(UsersGraphQlSchemaProvider.TYPE_USER, userControllers);
        return controllerResolvers;
    }

    private Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> getQueryControllers() {
        Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> queryControllers = new LinkedHashMap<>();
        queryControllers.put("tasks", (gqlPayload, parent) -> SearchTasksGraphQlController.getInstance().invoke(gqlPayload));
        queryControllers.put("task", (gqlPayload, parent) -> GetTaskGraphQlController.getInstance().invoke(gqlPayload));
        return queryControllers;
    }

    private Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> getMutationControllers() {
        Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> mutationControllers = new LinkedHashMap<>();
        mutationControllers.put("createTask", (gqlPayload, parent) -> CreateTaskGraphQlController.getInstance().invoke(gqlPayload));
        mutationControllers.put("updateTask", (gqlPayload, parent) -> UpdateTaskGraphQlController.getInstance().invoke(gqlPayload));
        mutationControllers.put("deleteTask", (gqlPayload, parent) -> DeleteTaskGraphQlController.getInstance().invoke(gqlPayload));
        return mutationControllers;
    }
}
