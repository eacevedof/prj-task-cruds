_HTML_ENTITIES = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    "\"": "&quot;",
    "'": "&#039;",
}


class HtmlEscaper:
    """
    El htmlspecialchars() de PHP (ENT_QUOTES): lo que se pinta dentro de un html sin que se interprete como html.
    """

    @staticmethod
    def getInstance() -> "HtmlEscaper":
        return HtmlEscaper()

    def getEscapedHtml(self, text: str) -> str:
        return "".join(_HTML_ENTITIES.get(character, character) for character in text)
