from graphql import (
    FieldNode,
    FragmentSpreadNode,
    GraphQLError,
    InlineFragmentNode,
    OperationDefinitionNode,
    SelectionSetNode,
    ValidationContext,
    ValidationRule,
)


def getQueryDepthRule(maxQueryDepth: int) -> type[ValidationRule]:
    """
    El QueryDepth de webonyx (GraphQL\\Validator\\Rules\\QueryDepth), que graphql-core no trae: cuenta los campos con
    subcampos anidados de cada operacion (los fragmentos suman como si estuvieran escritos en su sitio) y, si pasa
    de maxQueryDepth, la peticion no se ejecuta. Mismo mensaje que webonyx.
    """

    class QueryDepthRule(ValidationRule):

        def leave_operation_definition(self, operationDefinition: OperationDefinitionNode, *_args) -> None:
            maxDepth = _getFieldDepth(self.context, operationDefinition.selection_set, 0, 0, frozenset())
            if maxDepth <= maxQueryDepth:
                return

            # sin nodes: webonyx tampoco da locations en este error
            self.report_error(GraphQLError(f"Max query depth should be {maxQueryDepth} but got {maxDepth}."))

    return QueryDepthRule


def _getFieldDepth(
    validationContext: ValidationContext,
    selectionSet: SelectionSetNode | None,
    depth: int,
    maxDepth: int,
    visitedFragmentNames: frozenset,
) -> int:
    for childNode in (selectionSet.selections if selectionSet is not None else ()):
        maxDepth = _getNodeDepth(validationContext, childNode, depth, maxDepth, visitedFragmentNames)
    return maxDepth


def _getNodeDepth(
    validationContext: ValidationContext,
    node,
    depth: int,
    maxDepth: int,
    visitedFragmentNames: frozenset,
) -> int:
    if isinstance(node, FieldNode) and node.selection_set is not None:
        # el campo tiene hijos
        if depth > maxDepth:
            maxDepth = depth
        return _getFieldDepth(validationContext, node.selection_set, depth + 1, maxDepth, visitedFragmentNames)
    if isinstance(node, InlineFragmentNode):
        return _getFieldDepth(validationContext, node.selection_set, depth, maxDepth, visitedFragmentNames)
    if isinstance(node, FragmentSpreadNode):
        fragmentName = node.name.value
        fragment = validationContext.get_fragment(fragmentName)
        # un fragmento que se incluye a si mismo lo rechaza NoFragmentCyclesRule: aqui solo no se recorre otra vez
        if fragment is None or fragmentName in visitedFragmentNames:
            return maxDepth

        return _getFieldDepth(
            validationContext, fragment.selection_set, depth, maxDepth, visitedFragmentNames | {fragmentName}
        )
    return maxDepth
