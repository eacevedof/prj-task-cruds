from abc import ABC

from Modules.Shared.Infrastructure.Components.Html.HtmlEscaper import HtmlEscaper

_CSS_PATH = "/assets/Modules/Documentation/Css/documentation.css"
_JS_PATH = "/assets/Modules/Documentation/Js/documentation.js"


class AbstractView(ABC):
    """
    Esqueleto HTML de las paginas que se generan desde un .md (patron Documentation de app-ms-antiphising).
    Sus metodos son para los hijos (protected en PHP).
    """

    def getHtmlPage(self, title: str, htmlBody: str) -> str:
        title = HtmlEscaper.getInstance().getEscapedHtml(title)
        return "\n".join([
            "<!DOCTYPE html>",
            "<html lang=\"en\">",
            "<head>",
            "<meta charset=\"utf-8\">",
            "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">",
            f"<title>{title}</title>",
            f"<link rel=\"stylesheet\" href=\"{_CSS_PATH}\">",
            "</head>",
            "<body>",
            htmlBody,
            f"<script src=\"{_JS_PATH}\"></script>",
            "</body>",
            "</html>",
        ])

    def getReplacedContent(self, content: str, toReplace: dict) -> str:
        """
        Rellena los {{placeholder}} del .md: {"{{appVersion}}": "1.0.0", ...}
        """
        for placeholder, value in toReplace.items():
            content = content.replace(placeholder, value)
        return content
