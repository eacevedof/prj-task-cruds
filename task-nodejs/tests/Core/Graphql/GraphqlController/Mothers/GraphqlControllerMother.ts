import { GraphqlController } from "#app/Core/Graphql/GraphqlController.ts";

export class GraphqlControllerMother {

    static getControllerInstance(): GraphqlController {
        return new GraphqlController();
    }
}
