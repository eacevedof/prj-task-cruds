import { ExecuteGraphQlQueryController } from "#app/Modules/GraphQl/Infrastructure/Controllers/ExecuteGraphQlQueryController.ts";

export class GraphQlControllerMother {

    static getControllerInstance(): ExecuteGraphQlQueryController {
        return new ExecuteGraphQlQueryController();
    }
}
