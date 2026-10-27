import { readFileSync } from "node:fs";

import {
    buildSchema,
    defaultFieldResolver,
    executeSync,
    GraphQLError,
    parse,
    specifiedRules,
    validate,
    type DocumentNode,
    type GraphQLFieldResolver,
    type GraphQLSchema,
} from "graphql";

import { __ } from "#app/Core/config/global.ts";
import { getQueryDepthRule } from "#app/Core/Graphql/QueryDepthRule.ts";

import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import { GraphqlEnum } from "#app/Modules/Shared/Domain/Enums/GraphqlEnum.ts";
import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { HttpResponder } from "#app/Modules/Shared/Infrastructure/Components/HttpResponder/HttpResponder.ts";
import { AbstractApiController } from "#app/Modules/Shared/Infrastructure/Controllers/AbstractApiController.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";
import { CreateUserApiController } from "#app/Modules/Users/Infrastructure/Controllers/CreateUserApiController.ts";
import { DeleteUserApiController } from "#app/Modules/Users/Infrastructure/Controllers/DeleteUserApiController.ts";
import { GetUserApiController } from "#app/Modules/Users/Infrastructure/Controllers/GetUserApiController.ts";
import { ListUsersApiController } from "#app/Modules/Users/Infrastructure/Controllers/ListUsersApiController.ts";
import { LogInUserApiController } from "#app/Modules/Users/Infrastructure/Controllers/LogInUserApiController.ts";
import { RegenerateUserApiTokenApiController } from "#app/Modules/Users/Infrastructure/Controllers/RegenerateUserApiTokenApiController.ts";
import { UpdateUserApiController } from "#app/Modules/Users/Infrastructure/Controllers/UpdateUserApiController.ts";

import { TasksKeyEnum } from "#app/Modules/Tasks/Domain/Enums/TasksKeyEnum.ts";
import { CreateTaskApiController } from "#app/Modules/Tasks/Infrastructure/Controllers/CreateTaskApiController.ts";
import { DeleteTaskApiController } from "#app/Modules/Tasks/Infrastructure/Controllers/DeleteTaskApiController.ts";
import { GetTaskApiController } from "#app/Modules/Tasks/Infrastructure/Controllers/GetTaskApiController.ts";
import { SearchTasksApiController } from "#app/Modules/Tasks/Infrastructure/Controllers/SearchTasksApiController.ts";
import { UpdateTaskApiController } from "#app/Modules/Tasks/Infrastructure/Controllers/UpdateTaskApiController.ts";

/**
 * Tipo de schema.graphql => campo => fn(args, parent).
 */
type ResolversByType = Record<string, Record<string, (args: Primitives, parent: Primitives) => unknown>>;

// el schema se construye una vez por proceso; los resolvers de cada peticion le llegan como contextValue
let schema: GraphQLSchema | null = null;

/**
 * POST /graphql  body: {query, variables?, operationName?}  header: Authorization: Bearer <api_token>
 * (cada resolver autentica; sin token solo responden createUser y logIn).
 * Vive en Core y no en Shared porque cablea los resolvers de varios modulos (Shared no depende de ellos).
 * La respuesta es la de GraphQL ({errors?, data}), no el envelope REST: un error de dominio va en
 * errors[].extensions (ver AbstractGraphqlController) con status 200, como manda GraphQL sobre HTTP.
 */
export class GraphqlController extends AbstractApiController {

    readonly #listUsersApiController: ListUsersApiController;
    readonly #getUserApiController: GetUserApiController;
    readonly #createUserApiController: CreateUserApiController;
    readonly #logInUserApiController: LogInUserApiController;
    readonly #updateUserApiController: UpdateUserApiController;
    readonly #deleteUserApiController: DeleteUserApiController;
    readonly #regenerateUserApiTokenApiController: RegenerateUserApiTokenApiController;
    readonly #searchTasksApiController: SearchTasksApiController;
    readonly #getTaskApiController: GetTaskApiController;
    readonly #createTaskApiController: CreateTaskApiController;
    readonly #updateTaskApiController: UpdateTaskApiController;
    readonly #deleteTaskApiController: DeleteTaskApiController;

    constructor() {
        super();
        this.#listUsersApiController = ListUsersApiController.getInstance();
        this.#getUserApiController = GetUserApiController.getInstance();
        this.#createUserApiController = CreateUserApiController.getInstance();
        this.#logInUserApiController = LogInUserApiController.getInstance();
        this.#updateUserApiController = UpdateUserApiController.getInstance();
        this.#deleteUserApiController = DeleteUserApiController.getInstance();
        this.#regenerateUserApiTokenApiController = RegenerateUserApiTokenApiController.getInstance();
        this.#searchTasksApiController = SearchTasksApiController.getInstance();
        this.#getTaskApiController = GetTaskApiController.getInstance();
        this.#createTaskApiController = CreateTaskApiController.getInstance();
        this.#updateTaskApiController = UpdateTaskApiController.getInstance();
        this.#deleteTaskApiController = DeleteTaskApiController.getInstance();
    }

    static getInstance(): GraphqlController {
        return new GraphqlController();
    }

    execute(): HttpResponder {
        const query = this.httpRequest.getPostString(GraphqlEnum.KEY_QUERY);
        if (!query) {
            return this.#getGraphqlErrorResponse(
                ExceptionEnum.CODE_BAD_REQUEST,
                __(SharedMessageEnum.GRAPHQL_QUERY_IS_REQUIRED)
            );
        }

        try {
            return this.#getGraphqlResponse(ExceptionEnum.CODE_OK, this.#getExecutionResult(query));
        }
        catch (e) {
            this.logTrait.logException(getThrowable(e), "GraphqlController::execute");
            return this.#getGraphqlErrorResponse(
                ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
                __(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)
            );
        }
    }

    /**
     * El GraphQL::executeQuery()->toArray() de webonyx: parse + validacion (con la profundidad maxima) + ejecucion.
     * errors (si hay) delante de data, y data solo si no es null.
     */
    #getExecutionResult(query: string): Primitives {
        let document: DocumentNode;
        try {
            document = parse(query);
        }
        catch (e) {
            if (e instanceof GraphQLError) {
                return this.#getGraphqlResult([e]);
            }
            throw e;
        }

        const validationErrors = validate(
            this.#getSchema(),
            document,
            [...specifiedRules, getQueryDepthRule(GraphqlEnum.MAX_QUERY_DEPTH)]
        );
        if (validationErrors.length) {
            return this.#getGraphqlResult(validationErrors);
        }

        const executionResult = executeSync({
            schema: this.#getSchema(),
            document,
            contextValue: this.#getResolversByType(),
            variableValues: this.#getVariables(),
            operationName: this.httpRequest.getPostString(GraphqlEnum.KEY_OPERATION_NAME) || undefined,
            fieldResolver: this.#getFieldResolver(),
        });
        return this.#getGraphqlResult(executionResult.errors ?? [], executionResult.data);
    }

    #getGraphqlResult(errors: readonly GraphQLError[], data: unknown = null): Primitives {
        const graphqlResult: Primitives = {};
        if (errors.length) {
            graphqlResult[GraphqlEnum.KEY_ERRORS] = errors.map((error) => error.toJSON());
        }
        if (data != null) {
            graphqlResult[GraphqlEnum.KEY_DATA] = data;
        }
        return graphqlResult;
    }

    #getSchema(): GraphQLSchema {
        schema ??= buildSchema(readFileSync(new URL("schema.graphql", import.meta.url), "utf8"));
        return schema;
    }

    /**
     * Los campos sin resolver propio salen del objeto del ResultDto (resolver por defecto).
     */
    #getFieldResolver(): GraphQLFieldResolver<unknown, ResolversByType> {
        return (objectValue, args, resolversByType, resolveInfo) => {
            const resolver = resolversByType[resolveInfo.parentType.name]?.[resolveInfo.fieldName];
            return resolver
                ? resolver(args, objectValue as Primitives)
                : defaultFieldResolver(objectValue, args, resolversByType, resolveInfo);
        };
    }

    /**
     * Tipo de schema.graphql => campo => fn(args, parent).
     */
    #getResolversByType(): ResolversByType {
        return {
            [GraphqlEnum.TYPE_QUERY]: {
                users: () => this.#listUsersApiController.invoke(),
                user: (args) => this.#getUserApiController.invoke(args),
                tasks: (args) => this.#searchTasksApiController.invoke("", args),
                task: (args) => this.#getTaskApiController.invoke(args),
                userTasks: (args) => this.#searchTasksApiController.invoke(
                    getTrimmedString(args[TasksKeyEnum.USER_UUID]),
                    args
                ),
            },
            [GraphqlEnum.TYPE_MUTATION]: {
                createUser: (args) => this.#createUserApiController.invoke(args),
                logIn: (args) => this.#logInUserApiController.invoke(args),
                updateUser: (args) => this.#updateUserApiController.invoke(args),
                deleteUser: (args) => this.#deleteUserApiController.invoke(args),
                regenerateApiToken: (args) => this.#regenerateUserApiTokenApiController.invoke(args),
                createTask: (args) => this.#createTaskApiController.invoke(args),
                updateTask: (args) => this.#updateTaskApiController.invoke(args),
                deleteTask: (args) => this.#deleteTaskApiController.invoke(args),
            },
            [GraphqlEnum.TYPE_USER]: {
                tasks: (args, user) => this.#searchTasksApiController.invoke(
                    getTrimmedString(user[UsersKeyEnum.USER_UUID]),
                    args
                ),
            },
        };
    }

    /**
     * "variables" puede llegar como objeto JSON o, en algunos clientes, como string JSON.
     */
    #getVariables(): Primitives {
        let variables = this.httpRequest.getPost(GraphqlEnum.KEY_VARIABLES, {});
        if (typeof variables === "string") {
            try {
                variables = JSON.parse(variables);
            }
            catch {
                variables = {};
            }
        }
        return variables !== null && typeof variables === "object" && !Array.isArray(variables)
            ? variables as Primitives
            : {};
    }

    #getGraphqlErrorResponse(code: number, message: string): HttpResponder {
        return this.#getGraphqlResponse(code, {
            [GraphqlEnum.KEY_ERRORS]: [{ [GraphqlEnum.KEY_MESSAGE]: message }],
        });
    }

    #getGraphqlResponse(code: number, graphqlResult: Primitives): HttpResponder {
        return HttpResponder.json(JSON.stringify(graphqlResult), code);
    }
}
