package modules.tasks.infrastructure.controllers.searchtasksbyusergraphqlcontroller.mothers;

import modules.tasks.infrastructure.controllers.SearchTasksByUserGraphQlController;

public final class SearchTasksByUserGraphQlControllerMother {

    private SearchTasksByUserGraphQlControllerMother() {
    }

    public static SearchTasksByUserGraphQlController getControllerInstance() {
        return SearchTasksByUserGraphQlController.getInstance();
    }
}
