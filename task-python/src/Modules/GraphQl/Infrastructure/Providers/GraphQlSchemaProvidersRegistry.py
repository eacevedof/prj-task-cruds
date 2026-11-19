from pathlib import Path

from Modules.GraphQl.Infrastructure.Exceptions.GraphQlSchemaException import GraphQlSchemaException
from Modules.GraphQl.Infrastructure.Providers.InterfaceGraphQlSchemaProvider import (
    GraphQlResolversByType,
    InterfaceGraphQlSchemaProvider,
)

from Modules.Tasks.Infrastructure.Providers.TasksGraphQlSchemaProvider import TasksGraphQlSchemaProvider
from Modules.Users.Infrastructure.Providers.UsersGraphQlSchemaProvider import UsersGraphQlSchemaProvider


class GraphQlSchemaProvidersRegistry:
    """
    Une lo que registran los modulos en getAllModulesProviders(): la cabecera comun (schema.graphql) mas el
    SDL de cada proveedor, y la tabla tipo.campo => resolver. Es a /graphql lo que routes.py al ApiRouter.
    """

    def __init__(self):
        self._schemaProvidersInstances: list[InterfaceGraphQlSchemaProvider] = [
            providerClass.getInstance() for providerClass in self._getAllModulesProviders()
        ]

    @staticmethod
    def getInstance() -> "GraphQlSchemaProvidersRegistry":
        return GraphQlSchemaProvidersRegistry()

    def _getAllModulesProviders(self) -> list[type[InterfaceGraphQlSchemaProvider]]:
        """
        El orden importa: primero los que definen un tipo, despues los que lo extienden.
        Cada modulo tiene su propio provider
        """
        return [
            UsersGraphQlSchemaProvider,
            TasksGraphQlSchemaProvider,
        ]

    def getGraphQlSchemaAsString(self) -> str:
        """
        En el orden del registro: el modulo que define un tipo va antes que los que lo extienden.
        """
        allSchemas = [
            schemaProviderInstance.getGraphQlFileContent()
            for schemaProviderInstance in self._schemaProvidersInstances
        ]
        return "\n".join([
            (Path(__file__).parent / "schema.graphql").read_text(encoding="utf-8"),
            *allSchemas,
        ])

    def getAllMergedResolversByType(self) -> GraphQlResolversByType:
        """
        Une los getControllerResolvers() de cada provider en una sola tabla tipo => campo => resolver. Los tipos y
        campos salen en el orden del registro: primero los de Users y detras los que anade Tasks. Cada resolver es
        una funcion (gqlPayload, parent) que crea su controller solo si la consulta pide ese campo.

        "Query.tasks" y "User.tasks" no chocan: la clave de choque es tipo + campo, no solo el campo.

        @throws GraphQlSchemaException si dos modulos resuelven el mismo campo (uno pisaria al otro sin avisar)
        """
        resolversByType: GraphQlResolversByType = {}
        for schemaProviderInstance in self._schemaProvidersInstances:
            for typeName, controllerResolvers in schemaProviderInstance.getControllerResolvers().items():
                repeatedFields = [
                    fieldName for fieldName in controllerResolvers
                    if fieldName in resolversByType.get(typeName, {})
                ]
                if repeatedFields:
                    GraphQlSchemaException.fieldsAlreadyHaveResolver(
                        typeName,
                        repeatedFields,
                        type(schemaProviderInstance).__name__
                    )

                resolversByType[typeName] = {
                    **resolversByType.get(typeName, {}),
                    **controllerResolvers,
                }
        return resolversByType
