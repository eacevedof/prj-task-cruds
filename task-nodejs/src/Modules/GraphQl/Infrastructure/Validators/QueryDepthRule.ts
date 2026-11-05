import {
    GraphQLError,
    Kind,
    type ASTNode,
    type ASTVisitor,
    type OperationDefinitionNode,
    type SelectionSetNode,
    type ValidationContext,
    type ValidationRule,
} from "graphql";

/**
 * El QueryDepth de webonyx (GraphQL\Validator\Rules\QueryDepth), que graphql-js no trae: cuenta los campos con
 * subcampos anidados de cada operacion (los fragmentos suman como si estuvieran escritos en su sitio) y, si pasa
 * de maxQueryDepth, la peticion no se ejecuta. Mismo mensaje que webonyx.
 */
export function getQueryDepthRule(maxQueryDepth: number): ValidationRule {
    return (validationContext: ValidationContext): ASTVisitor => ({
        OperationDefinition: {
            leave(operationDefinition: OperationDefinitionNode): void {
                const maxDepth = getFieldDepth(validationContext, operationDefinition.selectionSet, 0, 0, new Set());
                if (maxDepth <= maxQueryDepth) return;

                // sin nodes: webonyx tampoco da locations en este error
                validationContext.reportError(new GraphQLError(
                    `Max query depth should be ${maxQueryDepth} but got ${maxDepth}.`
                ));
            },
        },
    });
}

function getFieldDepth(
    validationContext: ValidationContext,
    selectionSet: SelectionSetNode | undefined,
    depth: number,
    maxDepth: number,
    visitedFragmentNames: Set<string>
): number {
    for (const childNode of selectionSet?.selections ?? []) {
        maxDepth = getNodeDepth(validationContext, childNode, depth, maxDepth, visitedFragmentNames);
    }
    return maxDepth;
}

function getNodeDepth(
    validationContext: ValidationContext,
    node: ASTNode,
    depth: number,
    maxDepth: number,
    visitedFragmentNames: Set<string>
): number {
    if (node.kind === Kind.FIELD && node.selectionSet) {
        // el campo tiene hijos
        if (depth > maxDepth) {
            maxDepth = depth;
        }
        return getFieldDepth(validationContext, node.selectionSet, depth + 1, maxDepth, visitedFragmentNames);
    }
    if (node.kind === Kind.INLINE_FRAGMENT) {
        return getFieldDepth(validationContext, node.selectionSet, depth, maxDepth, visitedFragmentNames);
    }
    if (node.kind === Kind.FRAGMENT_SPREAD) {
        const fragmentName = node.name.value;
        const fragment = validationContext.getFragment(fragmentName);
        // un fragmento que se incluye a si mismo lo rechaza NoFragmentCyclesRule: aqui solo no se recorre otra vez
        if (!fragment || visitedFragmentNames.has(fragmentName)) return maxDepth;

        return getFieldDepth(
            validationContext, fragment.selectionSet, depth, maxDepth, new Set([...visitedFragmentNames, fragmentName])
        );
    }
    return maxDepth;
}
