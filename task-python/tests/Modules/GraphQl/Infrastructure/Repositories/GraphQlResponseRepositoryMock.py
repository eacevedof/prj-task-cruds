import json
from typing import Any

from Modules.GraphQl.Domain.Enums.GraphQlEnum import GraphQlEnum


class GraphQlResponseRepositoryMock:
    """
    Lectura de una respuesta GraphQL: {"errors": [{"message", "extensions": {"code", "errors"}}], "data": {...}}
    """

    def __init__(self, responseContent: str):
        self._responseArray: dict = json.loads(responseContent) or {}

    @staticmethod
    def fromHttpResponse(httpResponse: Any) -> "GraphQlResponseRepositoryMock":
        return GraphQlResponseRepositoryMock(httpResponse.getBody())

    def getData(self) -> dict:
        return self._responseArray.get(GraphQlEnum.KEY_DATA) or {}

    def getErrors(self) -> list:
        return self._responseArray.get(GraphQlEnum.KEY_ERRORS) or []

    def getFirstErrorCode(self) -> int:
        """
        Codigo HTTP equivalente REST del primer error (0 si es un error de GraphQL, sin extensions).
        """
        errors = self.getErrors()
        if not errors:
            return 0
        return int((errors[0].get("extensions") or {}).get(GraphQlEnum.EXTENSION_CODE) or 0)

    def getFirstErrorDetails(self) -> list:
        errors = self.getErrors()
        if not errors:
            return []
        return (errors[0].get("extensions") or {}).get(GraphQlEnum.EXTENSION_ERRORS) or []
