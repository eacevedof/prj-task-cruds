package modules.graphql.infrastructure.controllers.graphqlcontroller.mothers;

import modules.graphql.infrastructure.controllers.ExecuteGraphQlQueryController;

public final class GraphQlControllerMother {

    private GraphQlControllerMother() {
    }

    public static ExecuteGraphQlQueryController getControllerInstance() {
        return ExecuteGraphQlQueryController.getInstance();
    }
}
