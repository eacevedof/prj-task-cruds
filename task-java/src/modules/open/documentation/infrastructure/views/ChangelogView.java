package modules.open.documentation.infrastructure.views;

import java.util.Map;

import modules.shared.infrastructure.components.markdown.Markdowner;
import modules.shared.infrastructure.views.AbstractView;

/**
 * Publica changelog.md (una seccion por version; el titulo de la doc enlaza a #<version>).
 */
public final class ChangelogView extends AbstractView {

    private static final String TITLE = "Task Java CRUD API - changelog";

    private final Markdowner markdowner;

    private ChangelogView() {
        this.markdowner = Markdowner.getInstance();
    }

    public static ChangelogView getInstance() {
        return new ChangelogView();
    }

    public String getHtml(DocumentationViewDto documentationViewDto) {
        String markdown = getReplacedContent(getViewContent("changelog.md"), Map.of(
            "{{appBaseUrl}}", documentationViewDto.getAppBaseUrl()
        ));
        return getHtmlPage(TITLE, markdowner.getHtmlFromMarkdown(markdown));
    }
}
