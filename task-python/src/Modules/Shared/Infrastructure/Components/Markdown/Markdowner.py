import re

from Modules.Shared.Infrastructure.Components.Html.HtmlEscaper import HtmlEscaper

_CODE_BLOCK_PLACEHOLDER = "XZCODEBLOCKX%dXPLACEHOLDERX"
_INLINE_CODE_PLACEHOLDER = "XZINLINECODEX%dXPLACEHOLDERX"
_BLOCK_TAG_PREFIXES = ["<h", "<ul>", "<ol>", "<pre>", "<blockquote>", "<hr>", "<table"]


class Markdowner:
    """
    Markdown -> HTML sin vendor (port del Markdowner de app-ms-antiphising). Cubre lo que usa la documentacion:
    titulos (con id para enlazar #anclas), negrita/cursiva/tachado, enlaces, imagenes, citas, <hr>, listas,
    tablas GFM y codigo (en bloque y en linea, escapado). El resto del texto NO se escapa: el .md es nuestro.
    """

    def __init__(self):
        self._preAndCodeBlocks: list[str] = []
        self._inlineCodeBlocks: list[str] = []

    @staticmethod
    def getInstance() -> "Markdowner":
        return Markdowner()

    def getHtmlFromMarkdown(self, markdown: str) -> str:
        self._preAndCodeBlocks = []
        self._inlineCodeBlocks = []

        # un checkout en Windows (autocrlf) deja CRLF y los patrones de linea ($) no casarian
        html = self._getWithCodePlaceholders(markdown.replace("\r\n", "\n"))
        # imagenes antes que enlaces: "![alt](src)" contiene un "[alt](src)"
        html = self._getImgTags(html)
        html = self._getATags(html)
        html = self._getHTags(html)
        html = self._getTextFormattingTags(html)
        html = self._getBlockquoteTags(html)
        html = self._getHrTags(html)
        html = self._getListTags(html)
        # el codigo se restaura antes de tablas y parrafos: puede ir dentro de una celda
        html = self._getWithCodeRestored(html)
        html = self._getTableTags(html)
        return self._getPTags(html)

    def _getWithCodePlaceholders(self, markdown: str) -> str:
        """
        El codigo se aparta con un placeholder para que ninguna otra regla toque su contenido.
        """
        def replaceCodeBlock(match: re.Match) -> str:
            language, code = match.group(1), match.group(2)
            languageClass = f" class=\"language-{language}\"" if language else ""
            self._preAndCodeBlocks.append(f"<pre><code{languageClass}>{self._getEscapedHtml(code)}</code></pre>")
            return self._getPlaceholder(_CODE_BLOCK_PLACEHOLDER, len(self._preAndCodeBlocks) - 1)

        def replaceInlineCode(match: re.Match) -> str:
            self._inlineCodeBlocks.append(f"<code>{self._getEscapedHtml(match.group(1))}</code>")
            return self._getPlaceholder(_INLINE_CODE_PLACEHOLDER, len(self._inlineCodeBlocks) - 1)

        markdown = re.sub(r"```(\w*)\n?([\s\S]*?)```", replaceCodeBlock, markdown)
        return re.sub(r"`([^`\n]+)`", replaceInlineCode, markdown)

    def _getPlaceholder(self, placeholder: str, index: int) -> str:
        return placeholder.replace("%d", str(index), 1)

    def _getEscapedHtml(self, code: str) -> str:
        return HtmlEscaper.getInstance().getEscapedHtml(code)

    def _getImgTags(self, html: str) -> str:
        return re.sub(r"!\[([^\]]*)\]\(([^)]+)\)", lambda m: f"<img alt=\"{m.group(1)}\" src=\"{m.group(2)}\">", html)

    def _getATags(self, html: str) -> str:
        return re.sub(r"\[([^\]]+)\]\(([^)]+)\)", lambda m: f"<a href=\"{m.group(2)}\">{m.group(1)}</a>", html)

    def _getHTags(self, html: str) -> str:
        def replaceTitle(match: re.Match) -> str:
            level = len(match.group(1))
            title = match.group(2)
            return f"<h{level} id=\"{self._getAnchorId(title)}\">{title}</h{level}>"

        return re.sub(r"^(#{1,6})\s+(.*)$", replaceTitle, html, flags=re.MULTILINE)

    def _getAnchorId(self, title: str) -> str:
        """
        "2.1 Alta de usuario" -> "2-1-alta-de-usuario"; los puntos de una version se mantienen ("1.0.0").
        """
        anchorId = re.sub(r"<[^>]*>", "", title).lower()
        # letras y numeros (unicode) y el punto; el "_" tambien se sustituye, como \p{L}\p{N} en JS
        anchorId = re.sub(r"(?:_|[^\w.])+", "-", anchorId)
        return re.sub(r"^[-.]+|[-.]+\Z", "", anchorId)

    def _getTextFormattingTags(self, html: str) -> str:
        patterns = [
            (r"\*\*\*([^*\n]+)\*\*\*", r"<strong><em>\1</em></strong>"),
            (r"___([^_\n]+)___", r"<strong><em>\1</em></strong>"),
            (r"\*\*([^*\n]+)\*\*", r"<strong>\1</strong>"),
            (r"(?<!_)__([^_\n]+)__(?!_)", r"<strong>\1</strong>"),
            (r"(?<!\*)\*([^*\n]+)\*(?!\*)", r"<em>\1</em>"),
            # cursiva con _ solo entre espacios/bordes: "user_uuid" o "api_token" no son cursiva
            (r"(?<![\w_])_([^_\n]+)_(?![\w_])", r"<em>\1</em>"),
            (r"~~([^~\n]+)~~", r"<del>\1</del>"),
        ]
        for pattern, replacement in patterns:
            html = re.sub(pattern, replacement, html)
        return html

    def _getBlockquoteTags(self, html: str) -> str:
        return re.sub(r"^> (.*)$", r"<blockquote>\1</blockquote>", html, flags=re.MULTILINE)

    def _getHrTags(self, html: str) -> str:
        return re.sub(r"^(---|\*\*\*|___)[ \t\f\v\r]*$", "<hr>", html, flags=re.MULTILINE)

    def _getListTags(self, html: str) -> str:
        listLines: list[str] = []
        openListTag = ""
        for line in html.split("\n"):
            listTag = ""
            matches = re.match(r"^\s*[-*+]\s+(.*)$", line)
            if matches:
                listTag = "ul"
            else:
                matches = re.match(r"^\s*\d+\.\s+(.*)$", line)
                if matches:
                    listTag = "ol"

            if openListTag and listTag != openListTag:
                listLines.append(f"</{openListTag}>")
                openListTag = ""
            if not listTag or not matches:
                if line.strip() != "":
                    listLines.append(line)
                continue
            if not openListTag:
                listLines.append(f"<{listTag}>")
                openListTag = listTag
            listLines.append(f"<li>{matches.group(1)}</li>")
        if openListTag:
            listLines.append(f"</{openListTag}>")

        return "\n".join(listLines)

    def _getWithCodeRestored(self, html: str) -> str:
        for index, inlineCodeBlock in enumerate(self._inlineCodeBlocks):
            html = html.replace(self._getPlaceholder(_INLINE_CODE_PLACEHOLDER, index), inlineCodeBlock)
        for index, preAndCodeBlock in enumerate(self._preAndCodeBlocks):
            html = html.replace(self._getPlaceholder(_CODE_BLOCK_PLACEHOLDER, index), preAndCodeBlock)
        return html

    def _getTableTags(self, html: str) -> str:
        """
        Tabla = fila "| a | b |" seguida de una separadora "|---|:--:|" (que fija la alineacion).
        """
        lines = html.split("\n")
        tableLines: list[str] = []
        i = 0
        while i < len(lines):
            line = lines[i].strip()
            isTableStart = (
                line.startswith("|")
                and i + 1 < len(lines)
                and re.match(r"^\|[\s:|-]+\|\Z", lines[i + 1].strip()) is not None
            )
            if not isTableStart:
                tableLines.append(lines[i])
                i += 1
                continue

            alignments = self._getTableAlignments(lines[i + 1].strip())
            tableHtml = (
                "<table class=\"md-table\">\n<thead>\n<tr>"
                + self._getTableCells(line, "th", alignments)
                + "</tr>\n</thead>\n<tbody>"
            )
            i += 2
            while i < len(lines) and lines[i].strip().startswith("|"):
                tableHtml += f"\n<tr>{self._getTableCells(lines[i].strip(), 'td', alignments)}</tr>"
                i += 1
            tableLines.append(f"{tableHtml}\n</tbody>\n</table>")
        return "\n".join(tableLines)

    def _getTableCells(self, row: str, cellTag: str, alignments: list[str]) -> str:
        cellsHtml = ""
        for index, cell in enumerate(self._getTableRowCells(row)):
            alignment = alignments[index] if index < len(alignments) else ""
            style = f" style=\"text-align:{alignment}\"" if alignment else ""
            cellsHtml += f"<{cellTag}{style}>{cell.strip()}</{cellTag}>"
        return cellsHtml

    def _getTableRowCells(self, row: str) -> list[str]:
        return re.sub(r"^\|+|\|+\Z", "", row.strip()).split("|")

    def _getTableAlignments(self, separatorRow: str) -> list[str]:
        alignments = []
        for cell in self._getTableRowCells(separatorRow):
            cell = cell.strip()
            isLeft = cell.startswith(":")
            isRight = cell.endswith(":")
            if isLeft and isRight:
                alignments.append("center")
            elif isRight:
                alignments.append("right")
            elif isLeft:
                alignments.append("left")
            else:
                alignments.append("")
        return alignments

    def _getPTags(self, html: str) -> str:
        paragraphs = [
            paragraph for paragraph in re.sub(r"\n\n+", "\n\n", html).split("\n\n") if paragraph.strip() != ""
        ]

        def getParagraph(paragraph: str) -> str:
            paragraph = paragraph.strip()
            for blockTagPrefix in _BLOCK_TAG_PREFIXES:
                if paragraph.startswith(blockTagPrefix):
                    return paragraph
            if "<table" in paragraph or "<pre><code" in paragraph:
                return paragraph

            return f"<p>{paragraph.replace(chr(10), '<br>')}</p>"

        return "\n".join(getParagraph(paragraph) for paragraph in paragraphs)
