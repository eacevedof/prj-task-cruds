from abc import ABC, abstractmethod
from typing import Any, Callable

# fn(gqlPayload, parent): devuelve el objeto del ResultDto o lanza una GraphQlException.
GraphQlFieldResolver = Callable[[dict, Any], Any]

# Tipo => campo => resolver.
GraphQlResolversByType = dict[str, dict[str, GraphQlFieldResolver]]


class InterfaceGraphQlSchemaProvider(ABC):
    """
    Lo que un modulo aporta a /graphql: su parte del schema y quien resuelve cada campo. Cada modulo tiene el suyo en
    Infrastructure/Providers y se registra en GraphQlSchemaProvidersRegistry.getAllModulesProviders().
    """

    @staticmethod
    @abstractmethod
    def getInstance() -> "InterfaceGraphQlSchemaProvider":
        ...

    @abstractmethod
    def getGraphQlFileContent(self) -> str:
        """
        SDL del modulo (su <modulo>.graphql): define tipos o extiende los de otro modulo con "extend type".
        """

    @abstractmethod
    def getControllerResolvers(self) -> GraphQlResolversByType:
        """
        Tipo => campo => fn(gqlPayload, parent): devuelve el objeto del ResultDto o lanza una GraphQlException.
        """
