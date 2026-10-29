package modules.graphql.infrastructure.controllers.graphqlcontroller.mothers;

import modules.graphql.infrastructure.controllers.GraphqlController;

public final class GraphqlControllerMother {

    private GraphqlControllerMother() {
    }

    public static GraphqlController getControllerInstance() {
        return GraphqlController.getInstance();
    }
}
