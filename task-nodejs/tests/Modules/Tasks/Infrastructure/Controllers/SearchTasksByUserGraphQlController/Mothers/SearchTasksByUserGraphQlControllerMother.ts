import { SearchTasksByUserGraphQlController } from "#app/Modules/Tasks/Infrastructure/Controllers/SearchTasksByUserGraphQlController.ts";

export class SearchTasksByUserGraphQlControllerMother {

    static getControllerInstance(): SearchTasksByUserGraphQlController {
        return SearchTasksByUserGraphQlController.getInstance();
    }
}
