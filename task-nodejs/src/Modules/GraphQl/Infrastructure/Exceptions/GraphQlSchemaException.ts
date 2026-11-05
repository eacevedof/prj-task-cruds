import { __ } from "#app/Core/config/global.ts";

import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import type { InterfaceException } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { GraphQlMessageEnum } from "#app/Modules/GraphQl/Domain/Enums/GraphQlMessageEnum.ts";

/**
 * El registro de /graphql esta mal montado: es un fallo del programador, no de la peticion. Salta al montar el
 * schema, que en Node se monta una vez por proceso (en la primera peticion a /graphql), asi que rompe TODAS las
 * llamadas a /graphql (aunque la query no toque el campo afectado). La captura el catch de
 * ExecuteGraphQlQueryController: el mensaje va al log y el cliente solo recibe un 500 con el texto generico, sin data.
 */
export class GraphQlSchemaException extends Error implements InterfaceException {

    readonly #code: number;

    constructor(message: string, code: number) {
        super(message);
        this.name = "GraphQlSchemaException";
        this.#code = code;
    }

    /**
     * Dos providers registran resolver para el mismo tipo.campo: el segundo pisaria al primero sin avisar.
     * La lanza GraphQlSchemaProvidersRegistry.getAllMergedResolversByType() al unir los providers en el orden de
     * getAllModulesProviders().
     *
     * Ejemplo: TasksGraphQlSchemaProvider anade por error "user" en Query, que ya es de Users
     *   typeName            "Query"
     *   fieldNames          ["user"]  (o varios: ["tasks", "task"] -> "Query.tasks, task")
     *   schemaProviderClass "TasksGraphQlSchemaProvider"
     *                       (el que llega SEGUNDO y choca, no el que ya tenia el campo)
     *   mensaje: graphql: Query.user already has a resolver (TasksGraphQlSchemaProvider)
     *
     * @param typeName tipo del schema: "Query", "Mutation" o uno de negocio como "User"
     * @param fieldNames campos repetidos de ese tipo
     * @param schemaProviderClass nombre de la clase del provider que intenta registrarlos de nuevo
     * @throws GraphQlSchemaException
     */
    static fieldsAlreadyHaveResolver(
        typeName: string,
        fieldNames: string[],
        schemaProviderClass: string
    ): never {
        throw new GraphQlSchemaException(
            __(
                GraphQlMessageEnum.GRAPHQL_FIELDS_X_X_ALREADY_HAVE_RESOLVER_X,
                typeName,
                fieldNames.join(", "),
                schemaProviderClass
            ),
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
        );
    }

    /**
     * Un campo de Query o Mutation esta en el .graphql pero ningun provider le da resolver: sin esto devolveria null
     * sin avisar. La lanza ExecuteGraphQlQueryService.#failIfRootFieldWithoutResolver() tras montar el schema.
     * Solo se comprueban los tipos raiz: un campo de un tipo de negocio sin resolver (p. ej. User.tasks) no salta
     * aqui, lo resuelve el resolver por defecto leyendo del objeto padre (y daria null).
     *
     * Ejemplo: se anade "archiveTask(task_uuid: ID!): Task" al "extend type Mutation" de tasks.graphql y se olvida
     * el resolver en TasksGraphQlSchemaProvider.#getMutationControllers()
     *   typeName   "Mutation"
     *   fieldNames ["archiveTask"]
     *   mensaje: graphql: Mutation.archiveTask has no resolver
     *
     * @param typeName solo "Query" o "Mutation" (GraphQlEnum.TYPE_QUERY / TYPE_MUTATION)
     * @param fieldNames campos declarados en el schema sin resolver registrado
     * @throws GraphQlSchemaException
     */
    static fieldsWithoutResolver(
        typeName: string,
        fieldNames: string[]
    ): never {
        throw new GraphQlSchemaException(
            __(GraphQlMessageEnum.GRAPHQL_FIELDS_X_X_HAVE_NO_RESOLVER,
                typeName,
                fieldNames.join(", ")
            ),
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
        );
    }

    getCode(): number {
        return this.#code;
    }
}
