import json

from Modules.GraphQl.Domain.Enums.GraphQlEnum import GraphQlEnum


class ExecuteGraphQlRequestDto:
    """
    Body de POST /graphql: {query, variables?, operationName?}.
    """

    def __init__(self, query: str, variables: dict, operationName: str | None):
        self._query = query
        self._variables = variables
        self._operationName = operationName

    @staticmethod
    def fromPrimitives(primitives: dict) -> "ExecuteGraphQlRequestDto":
        query = primitives.get(GraphQlEnum.KEY_QUERY)
        variables = primitives.get(GraphQlEnum.KEY_VARIABLES)
        operationName = primitives.get(GraphQlEnum.KEY_OPERATION_NAME)
        return ExecuteGraphQlRequestDto(
            ExecuteGraphQlRequestDto._getScalarAsString(query if query is not None else ""),
            ExecuteGraphQlRequestDto._getVariablesAsObject(variables if variables is not None else {}),
            ExecuteGraphQlRequestDto._getScalarAsString(operationName if operationName is not None else "") or None,
        )

    @staticmethod
    def _getScalarAsString(value) -> str:
        """
        Un array/objeto donde va un string cuenta como vacio (como HttpRequester.getPostString), no como "{...}".
        is_scalar() ? trim((string) $value) : "": false es "" y true es "1", como en PHP.
        """
        if isinstance(value, str):
            return value.strip()
        if isinstance(value, bool):
            return "1" if value else ""
        if isinstance(value, (int, float)):
            return str(value)
        return ""

    @staticmethod
    def _getVariablesAsObject(variables) -> dict:
        """
        "variables" puede llegar como objeto JSON o, en algunos clientes, como string JSON.
        """
        if isinstance(variables, str):
            try:
                variables = json.loads(variables)
            except ValueError:
                variables = {}
        return variables if isinstance(variables, dict) else {}

    def getQuery(self) -> str:
        return self._query

    def getVariables(self) -> dict:
        return self._variables

    def getOperationName(self) -> str | None:
        return self._operationName
