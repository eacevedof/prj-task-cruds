from typing import NoReturn

from Core.config.global_ import __

from Modules.Shared.Domain.Enums.ExceptionEnum import ExceptionEnum
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import InterfaceException

from Modules.GraphQl.Domain.Enums.GraphQlMessageEnum import GraphQlMessageEnum


class GraphQlSchemaException(Exception, InterfaceException):
    """
    El registro de /graphql esta mal montado: es un fallo del programador, no de la peticion. Salta al montar el
    schema, que en Python se monta una vez por proceso (en la primera peticion a /graphql), asi que rompe TODAS las
    llamadas a /graphql (aunque la query no toque el campo afectado). La captura el catch de
    ExecuteGraphQlQueryController: el mensaje va al log y el cliente solo recibe un 500 con el texto generico, sin data.
    """

    def __init__(self, message: str, code: int):
        super().__init__(message)
        self._code = code

    @staticmethod
    def fieldsAlreadyHaveResolver(typeName: str, fieldNames: list[str], schemaProviderClass: str) -> NoReturn:
        """
        Dos providers registran resolver para el mismo tipo.campo: el segundo pisaria al primero sin avisar.
        La lanza GraphQlSchemaProvidersRegistry.getAllMergedResolversByType() al unir los providers en el orden de
        getAllModulesProviders().

        Ejemplo: TasksGraphQlSchemaProvider anade por error "user" en Query, que ya es de Users
          typeName            "Query"
          fieldNames          ["user"]  (o varios: ["tasks", "task"] -> "Query.tasks, task")
          schemaProviderClass "TasksGraphQlSchemaProvider"
                              (el que llega SEGUNDO y choca, no el que ya tenia el campo)
          mensaje: graphql: Query.user already has a resolver (TasksGraphQlSchemaProvider)

        @throws GraphQlSchemaException
        """
        raise GraphQlSchemaException(
            __(
                GraphQlMessageEnum.GRAPHQL_FIELDS_X_X_ALREADY_HAVE_RESOLVER_X,
                typeName,
                ", ".join(fieldNames),
                schemaProviderClass
            ),
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
        )

    @staticmethod
    def fieldsWithoutResolver(typeName: str, fieldNames: list[str]) -> NoReturn:
        """
        Un campo de Query o Mutation esta en el .graphql pero ningun provider le da resolver: sin esto devolveria null
        sin avisar. La lanza ExecuteGraphQlQueryService._failIfRootFieldWithoutResolver() tras montar el schema.
        Solo se comprueban los tipos raiz: un campo de un tipo de negocio sin resolver (p. ej. User.tasks) no salta
        aqui, lo resuelve el resolver por defecto leyendo del objeto padre (y daria null).

        Ejemplo: se anade "archiveTask(task_uuid: ID!): Task" al "extend type Mutation" de tasks.graphql y se olvida
        el resolver en TasksGraphQlSchemaProvider._getMutationControllers()
          typeName   "Mutation"
          fieldNames ["archiveTask"]
          mensaje: graphql: Mutation.archiveTask has no resolver

        @throws GraphQlSchemaException
        """
        raise GraphQlSchemaException(
            __(
                GraphQlMessageEnum.GRAPHQL_FIELDS_X_X_HAVE_NO_RESOLVER,
                typeName,
                ", ".join(fieldNames)
            ),
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
        )

    def getCode(self) -> int:
        return self._code
