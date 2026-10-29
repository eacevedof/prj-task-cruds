package modules.graphql.infrastructure.validators;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.HashSet;
import java.util.List;
import java.util.Map;
import java.util.Set;

import graphql.GraphQLError;
import graphql.GraphqlErrorBuilder;
import graphql.language.Document;
import graphql.language.Field;
import graphql.language.FragmentDefinition;
import graphql.language.FragmentSpread;
import graphql.language.InlineFragment;
import graphql.language.OperationDefinition;
import graphql.language.Selection;
import graphql.language.SelectionSet;

/**
 * El QueryDepth de webonyx (GraphQL\Validator\Rules\QueryDepth), que graphql-java no trae igual (su
 * MaxQueryDepthInstrumentation cuenta distinto y da otro mensaje): cuenta los campos con subcampos anidados de cada
 * operacion (los fragmentos suman como si estuvieran escritos en su sitio) y, si pasa de maxQueryDepth, la peticion no
 * se ejecuta. Mismo mensaje que webonyx.
 */
public final class QueryDepthRule {

    private final int maxQueryDepth;

    private QueryDepthRule(int maxQueryDepth) {
        this.maxQueryDepth = maxQueryDepth;
    }

    public static QueryDepthRule getInstance(int maxQueryDepth) {
        return new QueryDepthRule(maxQueryDepth);
    }

    /**
     * Un error por operacion que se pasa de profundidad (sin locations: webonyx tampoco las da en este error).
     */
    public List<GraphQLError> getErrors(Document document) {
        Map<String, FragmentDefinition> fragmentsByName = new HashMap<>();
        for (FragmentDefinition fragmentDefinition : document.getDefinitionsOfType(FragmentDefinition.class)) {
            fragmentsByName.put(fragmentDefinition.getName(), fragmentDefinition);
        }

        List<GraphQLError> errors = new ArrayList<>();
        for (OperationDefinition operationDefinition : document.getDefinitionsOfType(OperationDefinition.class)) {
            int maxDepth = getFieldDepth(fragmentsByName, operationDefinition.getSelectionSet(), 0, 0, new HashSet<>());
            if (maxDepth <= maxQueryDepth) continue;

            errors.add(GraphqlErrorBuilder.newError()
                .message("Max query depth should be %d but got %d.".formatted(maxQueryDepth, maxDepth))
                .build());
        }
        return errors;
    }

    private int getFieldDepth(
        Map<String, FragmentDefinition> fragmentsByName,
        SelectionSet selectionSet,
        int depth,
        int maxDepth,
        Set<String> visitedFragmentNames
    ) {
        if (selectionSet == null) return maxDepth;

        for (Selection<?> childNode : selectionSet.getSelections()) {
            maxDepth = getNodeDepth(fragmentsByName, childNode, depth, maxDepth, visitedFragmentNames);
        }
        return maxDepth;
    }

    private int getNodeDepth(
        Map<String, FragmentDefinition> fragmentsByName,
        Selection<?> node,
        int depth,
        int maxDepth,
        Set<String> visitedFragmentNames
    ) {
        if (node instanceof Field field && field.getSelectionSet() != null) {
            // el campo tiene hijos
            if (depth > maxDepth) {
                maxDepth = depth;
            }
            return getFieldDepth(fragmentsByName, field.getSelectionSet(), depth + 1, maxDepth, visitedFragmentNames);
        }
        if (node instanceof InlineFragment inlineFragment) {
            return getFieldDepth(fragmentsByName, inlineFragment.getSelectionSet(), depth, maxDepth, visitedFragmentNames);
        }
        if (node instanceof FragmentSpread fragmentSpread) {
            String fragmentName = fragmentSpread.getName();
            FragmentDefinition fragment = fragmentsByName.get(fragmentName);
            // un fragmento que se incluye a si mismo lo rechaza la validacion (NoFragmentCycles): aqui solo no se
            // recorre otra vez
            if (fragment == null || visitedFragmentNames.contains(fragmentName)) return maxDepth;

            Set<String> fragmentNames = new HashSet<>(visitedFragmentNames);
            fragmentNames.add(fragmentName);
            return getFieldDepth(fragmentsByName, fragment.getSelectionSet(), depth, maxDepth, fragmentNames);
        }
        return maxDepth;
    }
}
