import { HtmlEscaper } from "#app/Modules/Shared/Infrastructure/Components/Html/HtmlEscaper.ts";

const CSS_PATH = "/assets/Modules/Documentation/Css/documentation.css";
const JS_PATH = "/assets/Modules/Documentation/Js/documentation.js";

/**
 * Esqueleto HTML de las paginas que se generan desde un .md (patron Documentation de app-ms-antiphising).
 */
export abstract class AbstractView {

    protected getHtmlPage(title: string, htmlBody: string): string {
        title = HtmlEscaper.getInstance().getEscapedHtml(title);
        return [
            "<!DOCTYPE html>",
            "<html lang=\"en\">",
            "<head>",
            "<meta charset=\"utf-8\">",
            "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">",
            `<title>${title}</title>`,
            `<link rel="stylesheet" href="${CSS_PATH}">`,
            "</head>",
            "<body>",
            htmlBody,
            `<script src="${JS_PATH}"></script>`,
            "</body>",
            "</html>",
        ].join("\n");
    }

    /**
     * Rellena los {{placeholder}} del .md: {"{{appVersion}}": "1.0.0", ...}
     */
    protected getReplacedContent(content: string, toReplace: Record<string, string>): string {
        for (const [placeholder, value] of Object.entries(toReplace)) {
            content = content.replaceAll(placeholder, value);
        }
        return content;
    }
}
