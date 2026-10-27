import { __ } from "#app/Core/config/global.ts";

import { DocumentationView } from "#app/Modules/Open/Documentation/Infrastructure/Views/DocumentationView.ts";
import { DocumentationViewDto } from "#app/Modules/Open/Documentation/Infrastructure/Views/DocumentationViewDto.ts";
import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { HttpResponder } from "#app/Modules/Shared/Infrastructure/Components/HttpResponder/HttpResponder.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { EnvironmentReaderRawRepository } from "#app/Modules/Shared/Infrastructure/Repositories/Configuration/EnvironmentReaderRawRepository.ts";
import { LogTrait } from "#app/Modules/Shared/Infrastructure/Traits/LogTrait.ts";

/**
 * GET /api/v1  (publica, HTML generado desde documentation.md)
 */
export class DocumentationController {

    readonly #logTrait = LogTrait.getInstance();

    static getInstance(): DocumentationController {
        return new DocumentationController();
    }

    show(): HttpResponder {
        try {
            const environmentReaderRawRepository = EnvironmentReaderRawRepository.getInstance();
            return HttpResponder.html(
                DocumentationView.getInstance().getHtml(
                    DocumentationViewDto.fromPrimitives({
                        appVersion: environmentReaderRawRepository.getAppVersion(),
                        appVersionUpdate: environmentReaderRawRepository.getAppVersionUpdate(),
                        appBaseUrl: environmentReaderRawRepository.getBaseUrl(),
                    })
                )
            );
        }
        catch (e) {
            this.#logTrait.logException(getThrowable(e), "DocumentationController::show");
            return HttpResponder.html(
                `<h1>${__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)}</h1>`,
                ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
            );
        }
    }
}
