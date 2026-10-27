package modules.open.documentation.infrastructure.controllers.documentationcontroller.mothers;

import modules.open.documentation.infrastructure.controllers.ChangelogController;
import modules.open.documentation.infrastructure.controllers.DocumentationController;

public final class DocumentationControllerMother {

    private DocumentationControllerMother() {
    }

    public static DocumentationController getControllerInstance() {
        return DocumentationController.getInstance();
    }

    public static ChangelogController getChangelogControllerInstance() {
        return ChangelogController.getInstance();
    }
}
