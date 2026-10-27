package modules.shared.infrastructure.views;

import java.io.IOException;
import java.io.InputStream;
import java.io.UncheckedIOException;
import java.nio.charset.StandardCharsets;
import java.util.Map;

import modules.shared.infrastructure.components.html.HtmlEscaper;

/**
 * Esqueleto HTML de las paginas que se generan desde un .md (patron Documentation de app-ms-antiphising).
 */
public abstract class AbstractView {

    private static final String CSS_PATH = "/assets/Modules/Documentation/Css/documentation.css";
    private static final String JS_PATH = "/assets/Modules/Documentation/Js/documentation.js";

    protected String getHtmlPage(String title, String htmlBody) {
        title = HtmlEscaper.getInstance().getEscapedHtml(title);
        return String.join("\n",
            "<!DOCTYPE html>",
            "<html lang=\"en\">",
            "<head>",
            "<meta charset=\"utf-8\">",
            "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">",
            "<title>" + title + "</title>",
            "<link rel=\"stylesheet\" href=\"" + CSS_PATH + "\">",
            "</head>",
            "<body>",
            htmlBody,
            "<script src=\"" + JS_PATH + "\"></script>",
            "</body>",
            "</html>"
        );
    }

    /**
     * Rellena los {{placeholder}} del .md: {"{{appVersion}}": "1.0.0", ...}
     */
    protected String getReplacedContent(String content, Map<String, String> toReplace) {
        for (Map.Entry<String, String> replacement : toReplace.entrySet()) {
            content = content.replace(replacement.getKey(), replacement.getValue());
        }
        return content;
    }

    /**
     * El .md que vive junto a la clase de la vista (va en el classpath, como en task-php junto al .php).
     * @throws UncheckedIOException
     */
    protected String getViewContent(String fileName) {
        try (InputStream inputStream = getClass().getResourceAsStream(fileName)) {
            if (inputStream == null) {
                throw new UncheckedIOException(new IOException("view not found: " + fileName));
            }
            return new String(inputStream.readAllBytes(), StandardCharsets.UTF_8);
        }
        catch (IOException e) {
            throw new UncheckedIOException(e);
        }
    }
}
