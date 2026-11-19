import inspect
from typing import Any, Callable

from graphql import (
    GraphQLError,
    GraphQLObjectType,
    GraphQLSchema,
    build_schema,
    default_field_resolver,
    execute_sync,
    parse,
    specified_rules,
    validate,
)

from Modules.GraphQl.Domain.Enums.GraphQlEnum import GraphQlEnum
from Modules.GraphQl.Application.ExecuteGraphQlQuery.ExecuteGraphQlRequestDto import ExecuteGraphQlRequestDto
from Modules.GraphQl.Application.ExecuteGraphQlQuery.ExecuteGraphQlResultDto import ExecuteGraphQlResultDto
from Modules.GraphQl.Infrastructure.Exceptions.GraphQlSchemaException import GraphQlSchemaException
from Modules.GraphQl.Infrastructure.Providers.GraphQlSchemaProvidersRegistry import GraphQlSchemaProvidersRegistry
from Modules.GraphQl.Infrastructure.Providers.InterfaceGraphQlSchemaProvider import GraphQlResolversByType
from Modules.GraphQl.Infrastructure.Validators.QueryDepthRule import getQueryDepthRule

# el schema no depende de la peticion (los resolvers crean su XxxGraphQlController al llamarse): uno por proceso
_executeGraphQlQueryService: "ExecuteGraphQlQueryService | None" = None


class ExecuteGraphQlQueryService:
    """
    Monta el schema con lo que registran los modulos y ejecuta la peticion con graphql-core: el unico sitio (junto a
    GraphQlException y QueryDepthRule) que conoce la libreria. Servicio: no captura ni loguea.
    """

    def __init__(self, graphqlSchemaRegistry: GraphQlSchemaProvidersRegistry):
        self._graphqlSchemaRegistry = graphqlSchemaRegistry
        # (schema, fieldResolver): el schema ya montado y la tabla de resolvers con la que se ejecuta
        self._executableSchema: tuple[GraphQLSchema, Callable[..., Any]] | None = None

    @staticmethod
    def getInstance() -> "ExecuteGraphQlQueryService":
        global _executeGraphQlQueryService
        if _executeGraphQlQueryService is None:
            _executeGraphQlQueryService = ExecuteGraphQlQueryService(GraphQlSchemaProvidersRegistry.getInstance())
        return _executeGraphQlQueryService

    def invoke(self, executeGraphQlRequestDto: ExecuteGraphQlRequestDto) -> ExecuteGraphQlResultDto:
        """
        El GraphQL::executeQuery() de webonyx: parse + validacion (todas las reglas y la profundidad maxima) + ejecucion.
        Los errores de GraphQL (sintaxis, validacion, dominio de un resolver) van en el resultado, no se lanzan.

        @throws GraphQlSchemaException si el registro esta mal montado (campo repetido o sin resolver)
        """
        schema, fieldResolver = self._getExecutableSchema()

        try:
            document = parse(executeGraphQlRequestDto.getQuery())
        except GraphQLError as e:
            return self._getExecuteGraphQlResultDto([e])

        validationErrors = validate(
            schema,
            document,
            [*specified_rules, getQueryDepthRule(GraphQlEnum.MAX_QUERY_DEPTH)]
        )
        if validationErrors:
            return self._getExecuteGraphQlResultDto(validationErrors)

        executionResult = execute_sync(
            schema,
            document,
            variable_values=executeGraphQlRequestDto.getVariables(),
            operation_name=executeGraphQlRequestDto.getOperationName(),
            field_resolver=fieldResolver,
        )
        return self._getExecuteGraphQlResultDto(executionResult.errors or [], executionResult.data)

    def _getExecuteGraphQlResultDto(self, errors: list, data=None) -> ExecuteGraphQlResultDto:
        """
        El ExecutionResult::toArray() de webonyx: cada error serializado y data tal cual.
        """
        return ExecuteGraphQlResultDto.fromPrimitives({
            GraphQlEnum.KEY_ERRORS: [error.formatted for error in errors],
            GraphQlEnum.KEY_DATA: data,
        })

    def _getExecutableSchema(self) -> tuple[GraphQLSchema, Callable[..., Any]]:
        """
        @throws GraphQlSchemaException
        """
        if self._executableSchema is not None:
            return self._executableSchema

        resolversByType = self._graphqlSchemaRegistry.getAllMergedResolversByType()
        # todos los esquemas .graphql de los modulos, en el orden del registro, con la cabecera comun schema.graphql
        schema = build_schema(self._graphqlSchemaRegistry.getGraphQlSchemaAsString())
        self._failIfRootFieldWithoutResolver(schema, resolversByType)

        def fieldResolver(objectValue, resolveInfo, **gqlPayload):
            # graphql-core no tiene el typeConfigDecorator de webonyx: los campos sin resolver propio salen del objeto
            # del ResultDto (resolver por defecto)
            resolver = resolversByType.get(resolveInfo.parent_type.name, {}).get(resolveInfo.field_name)
            if resolver is not None:
                # un resolver que no usa el padre (lambda gqlPayload=None: ...) solo recibe el payload
                if len(inspect.signature(resolver).parameters) < 2:
                    return resolver(gqlPayload)
                return resolver(gqlPayload, objectValue)
            return default_field_resolver(objectValue, resolveInfo, **gqlPayload)

        self._executableSchema = (schema, fieldResolver)
        return self._executableSchema

    def _failIfRootFieldWithoutResolver(self, schema: GraphQLSchema, resolversByType: GraphQlResolversByType) -> None:
        """
        Un campo de Query o Mutation sin resolver devolveria null sin avisar: es un fallo del registro.

        @throws GraphQlSchemaException
        """
        for rootTypeName in [GraphQlEnum.TYPE_QUERY, GraphQlEnum.TYPE_MUTATION]:
            rootType = schema.get_type(rootTypeName)
            assert isinstance(rootType, GraphQLObjectType)
            fieldsWithoutResolver = [
                fieldName for fieldName in rootType.fields
                if fieldName not in resolversByType.get(rootTypeName, {})
            ]
            if fieldsWithoutResolver:
                GraphQlSchemaException.fieldsWithoutResolver(rootTypeName, fieldsWithoutResolver)
