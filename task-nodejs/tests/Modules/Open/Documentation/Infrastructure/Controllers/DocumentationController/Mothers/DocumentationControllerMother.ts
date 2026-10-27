import { ChangelogController } from "#app/Modules/Open/Documentation/Infrastructure/Controllers/ChangelogController.ts";
import { DocumentationController } from "#app/Modules/Open/Documentation/Infrastructure/Controllers/DocumentationController.ts";

export class DocumentationControllerMother {

    static getControllerInstance(): DocumentationController {
        return new DocumentationController();
    }

    static getChangelogControllerInstance(): ChangelogController {
        return new ChangelogController();
    }
}
