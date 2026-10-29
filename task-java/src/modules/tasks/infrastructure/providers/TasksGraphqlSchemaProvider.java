package modules.tasks.infrastructure.providers;

import java.util.LinkedHashMap;
import java.util.Map;
import java.util.function.BiFunction;

import modules.shared.application.Primitives;

import modules.graphql.domain.enums.GraphqlEnum;
import modules.graphql.infrastructure.schema.GraphqlSchemaRegistry;
import modules.graphql.infrastructure.schema.InterfaceGraphqlSchemaProvider;

import modules.users.domain.enums.UsersKeyEnum;

import modules.tasks.domain.enums.TasksKeyEnum;
import modules.tasks.infrastructure.controllers.CreateTaskGraphqlController;
import modules.tasks.infrastructure.controllers.DeleteTaskGraphqlController;
import modules.tasks.infrastructure.controllers.GetTaskGraphqlController;
import modules.tasks.infrastructure.controllers.SearchTasksByUserGraphqlController;
import modules.tasks.infrastructure.controllers.SearchTasksGraphqlController;
import modules.tasks.infrastructure.controllers.UpdateTaskGraphqlController;

/**
 * Parte de tasks en /graphql: tasks.graphql (extiende Query, Mutation y User de users) y sus resolvers, incluido
 * User.tasks, que cuelga de User pero es de tasks. Va despues de UsersGraphqlSchemaProvider en el registro.
 */
public final class TasksGraphqlSchemaProvider implements InterfaceGraphqlSchemaProvider {

    public static TasksGraphqlSchemaProvider getInstance() {
        return new TasksGraphqlSchemaProvider();
    }

    @Override
    public String getSdl() {
        return GraphqlSchemaRegistry.getResourceContent(TasksGraphqlSchemaProvider.class, "tasks.graphql");
    }

    @Override
    public Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> getResolversByType() {
        Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> queryResolvers = new LinkedHashMap<>();
        queryResolvers.put("tasks", (args, parent) -> SearchTasksGraphqlController.getInstance().invoke(args));
        queryResolvers.put("task", (args, parent) -> GetTaskGraphqlController.getInstance().invoke(args));
        queryResolvers.put("userTasks", (args, parent) -> SearchTasksByUserGraphqlController.getInstance().invoke(
            Primitives.getTrimmedString(args.get(TasksKeyEnum.USER_UUID)),
            args
        ));

        Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> mutationResolvers = new LinkedHashMap<>();
        mutationResolvers.put("createTask", (args, parent) -> CreateTaskGraphqlController.getInstance().invoke(args));
        mutationResolvers.put("updateTask", (args, parent) -> UpdateTaskGraphqlController.getInstance().invoke(args));
        mutationResolvers.put("deleteTask", (args, parent) -> DeleteTaskGraphqlController.getInstance().invoke(args));

        Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>> userResolvers = new LinkedHashMap<>();
        userResolvers.put("tasks", (args, user) -> SearchTasksByUserGraphqlController.getInstance().invoke(
            Primitives.getTrimmedString(user.get(UsersKeyEnum.USER_UUID)),
            args
        ));

        Map<String, Map<String, BiFunction<Map<String, Object>, Map<String, Object>, Object>>> resolversByType = new LinkedHashMap<>();
        resolversByType.put(GraphqlEnum.TYPE_QUERY, queryResolvers);
        resolversByType.put(GraphqlEnum.TYPE_MUTATION, mutationResolvers);
        resolversByType.put(GraphqlEnum.TYPE_USER, userResolvers);
        return resolversByType;
    }
}
