package modules.open.documentation.infrastructure.views;

import java.util.Map;

import modules.shared.infrastructure.components.markdown.Markdowner;
import modules.shared.infrastructure.views.AbstractView;

/**
 * Publica documentation.md: TODA la documentacion de la API vive en ese .md (aqui no hay texto).
 */
public final class DocumentationView extends AbstractView {

    private static final String TITLE = "Task Java CRUD API";

    private final Markdowner markdowner;

    private DocumentationView() {
        this.markdowner = Markdowner.getInstance();
    }

    public static DocumentationView getInstance() {
        return new DocumentationView();
    }

    public String getHtml(DocumentationViewDto documentationViewDto) {
        String markdown = getReplacedContent(getViewContent("documentation.md"), Map.of(
            "{{appVersion}}", documentationViewDto.getAppVersion(),
            "{{appVersionUpdate}}", documentationViewDto.getAppVersionUpdate(),
            "{{appBaseUrl}}", documentationViewDto.getAppBaseUrl()
        ));
        return getHtmlPage(TITLE, markdowner.getHtmlFromMarkdown(markdown));
    }
}
