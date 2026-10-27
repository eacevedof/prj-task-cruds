package core.graphql.graphqlcontroller.mothers;

import core.graphql.GraphqlController;

public final class GraphqlControllerMother {

    private GraphqlControllerMother() {
    }

    public static GraphqlController getControllerInstance() {
        return GraphqlController.getInstance();
    }
}
