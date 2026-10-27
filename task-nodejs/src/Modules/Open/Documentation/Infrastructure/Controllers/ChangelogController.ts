import { __ } from "#app/Core/config/global.ts";

import { ChangelogView } from "#app/Modules/Open/Documentation/Infrastructure/Views/ChangelogView.ts";
import { DocumentationViewDto } from "#app/Modules/Open/Documentation/Infrastructure/Views/DocumentationViewDto.ts";
import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { HttpResponder } from "#app/Modules/Shared/Infrastructure/Components/HttpResponder/HttpResponder.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { EnvironmentReaderRawRepository } from "#app/Modules/Shared/Infrastructure/Repositories/Configuration/EnvironmentReaderRawRepository.ts";
import { LogTrait } from "#app/Modules/Shared/Infrastructure/Traits/LogTrait.ts";

/**
 * GET /api/v1/changelog  (publica, HTML generado desde changelog.md)
 */
export class ChangelogController {

    readonly #logTrait = LogTrait.getInstance();

    static getInstance(): ChangelogController {
        return new ChangelogController();
    }

    show(): HttpResponder {
        try {
            return HttpResponder.html(
                ChangelogView.getInstance().getHtml(
                    DocumentationViewDto.fromPrimitives({
                        appBaseUrl: EnvironmentReaderRawRepository.getInstance().getBaseUrl(),
                    })
                )
            );
        }
        catch (e) {
            this.#logTrait.logException(getThrowable(e), "ChangelogController::show");
            return HttpResponder.html(
                `<h1>${__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)}</h1>`,
                ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
            );
        }
    }
}
