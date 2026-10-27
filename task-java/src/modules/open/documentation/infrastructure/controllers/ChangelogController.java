package modules.open.documentation.infrastructure.controllers;

import modules.open.documentation.infrastructure.views.ChangelogView;
import modules.open.documentation.infrastructure.views.DocumentationViewDto;
import modules.shared.application.Primitives;
import modules.shared.domain.enums.ExceptionEnum;
import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.components.httpresponder.HttpResponder;
import modules.shared.infrastructure.repositories.configuration.EnvironmentReaderRawRepository;
import modules.shared.infrastructure.traits.LogTrait;

import static core.config.Global.__;

/**
 * GET /api/v1/changelog  (publica, HTML generado desde changelog.md)
 */
public final class ChangelogController {

    private final LogTrait logTrait = LogTrait.getInstance();

    public static ChangelogController getInstance() {
        return new ChangelogController();
    }

    public HttpResponder show() {
        try {
            return HttpResponder.html(
                ChangelogView.getInstance().getHtml(
                    DocumentationViewDto.fromPrimitives(Primitives.of(
                        "appBaseUrl", EnvironmentReaderRawRepository.getInstance().getBaseUrl()
                    ))
                )
            );
        }
        catch (Exception e) {
            logTrait.logException(e, "ChangelogController::show");
            return HttpResponder.html(
                "<h1>" + __(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED) + "</h1>",
                ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
            );
        }
    }
}
