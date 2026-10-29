import { GraphqlController } from "#app/Modules/Graphql/Infrastructure/Controllers/GraphqlController.ts";

export class GraphqlControllerMother {

    static getControllerInstance(): GraphqlController {
        return new GraphqlController();
    }
}
