package modules.open.documentation.infrastructure.controllers;

import modules.open.documentation.infrastructure.views.DocumentationView;
import modules.open.documentation.infrastructure.views.DocumentationViewDto;
import modules.shared.application.Primitives;
import modules.shared.domain.enums.ExceptionEnum;
import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.components.httpresponder.HttpResponder;
import modules.shared.infrastructure.repositories.configuration.EnvironmentReaderRawRepository;
import modules.shared.infrastructure.traits.LogTrait;

import static core.config.Global.__;

/**
 * GET /api/v1  (publica, HTML generado desde documentation.md)
 */
public final class DocumentationController {

    private final LogTrait logTrait = LogTrait.getInstance();

    public static DocumentationController getInstance() {
        return new DocumentationController();
    }

    public HttpResponder show() {
        try {
            EnvironmentReaderRawRepository environmentReaderRawRepository = EnvironmentReaderRawRepository.getInstance();
            return HttpResponder.html(
                DocumentationView.getInstance().getHtml(
                    DocumentationViewDto.fromPrimitives(Primitives.of(
                        "appVersion", environmentReaderRawRepository.getAppVersion(),
                        "appVersionUpdate", environmentReaderRawRepository.getAppVersionUpdate(),
                        "appBaseUrl", environmentReaderRawRepository.getBaseUrl()
                    ))
                )
            );
        }
        catch (Exception e) {
            logTrait.logException(e, "DocumentationController::show");
            return HttpResponder.html(
                "<h1>" + __(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED) + "</h1>",
                ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
            );
        }
    }
}
