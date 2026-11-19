from Modules.GraphQl.Domain.Enums.GraphQlEnum import GraphQlEnum


class ExecuteGraphQlResultDto:
    """
    Respuesta GraphQL {errors?, data?}: las claves solo van si traen algo y en el orden de webonyx (errors primero).
    """

    def __init__(self, errors: list, data: dict | None):
        self._errors = errors
        self._data = data

    @staticmethod
    def fromPrimitives(graphqlResult: dict) -> "ExecuteGraphQlResultDto":
        """
        graphqlResult: el ExecutionResult de graphql-core ya serializado ({errors?, data?}), como el toArray() de webonyx
        """
        errors = graphqlResult.get(GraphQlEnum.KEY_ERRORS)
        data = graphqlResult.get(GraphQlEnum.KEY_DATA)
        return ExecuteGraphQlResultDto(
            errors if isinstance(errors, list) else [],
            data if isinstance(data, dict) else None,
        )

    @staticmethod
    def fromErrorMessage(message: str) -> "ExecuteGraphQlResultDto":
        """
        Error antes de ejecutar (peticion sin query, fallo inesperado): sin data, como lo daria GraphQL.
        """
        return ExecuteGraphQlResultDto([{GraphQlEnum.KEY_MESSAGE: message}], None)

    def toArray(self) -> dict:
        graphqlResult: dict = {}
        if self._errors:
            graphqlResult[GraphQlEnum.KEY_ERRORS] = self._errors
        if self._data is not None:
            graphqlResult[GraphQlEnum.KEY_DATA] = self._data
        return graphqlResult
