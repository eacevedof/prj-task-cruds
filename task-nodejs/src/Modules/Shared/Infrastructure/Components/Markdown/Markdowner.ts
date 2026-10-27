import { HtmlEscaper } from "#app/Modules/Shared/Infrastructure/Components/Html/HtmlEscaper.ts";

const CODE_BLOCK_PLACEHOLDER = "XZCODEBLOCKX%dXPLACEHOLDERX";
const INLINE_CODE_PLACEHOLDER = "XZINLINECODEX%dXPLACEHOLDERX";
const BLOCK_TAG_PREFIXES = ["<h", "<ul>", "<ol>", "<pre>", "<blockquote>", "<hr>", "<table"];

/**
 * Markdown -> HTML sin vendor (port del Markdowner de app-ms-antiphising). Cubre lo que usa la documentacion:
 * titulos (con id para enlazar #anclas), negrita/cursiva/tachado, enlaces, imagenes, citas, <hr>, listas,
 * tablas GFM y codigo (en bloque y en linea, escapado). El resto del texto NO se escapa: el .md es nuestro.
 */
export class Markdowner {

    #preAndCodeBlocks: string[] = [];
    #inlineCodeBlocks: string[] = [];

    static getInstance(): Markdowner {
        return new Markdowner();
    }

    getHtmlFromMarkdown(markdown: string): string {
        this.#preAndCodeBlocks = [];
        this.#inlineCodeBlocks = [];

        // un checkout en Windows (autocrlf) deja CRLF y los patrones de linea ($) no casarian
        let html = this.#getWithCodePlaceholders(markdown.replaceAll("\r\n", "\n"));
        // imagenes antes que enlaces: "![alt](src)" contiene un "[alt](src)"
        html = this.#getImgTags(html);
        html = this.#getATags(html);
        html = this.#getHTags(html);
        html = this.#getTextFormattingTags(html);
        html = this.#getBlockquoteTags(html);
        html = this.#getHrTags(html);
        html = this.#getListTags(html);
        // el codigo se restaura antes de tablas y parrafos: puede ir dentro de una celda
        html = this.#getWithCodeRestored(html);
        html = this.#getTableTags(html);
        return this.#getPTags(html);
    }

    /**
     * El codigo se aparta con un placeholder para que ninguna otra regla toque su contenido.
     */
    #getWithCodePlaceholders(markdown: string): string {
        markdown = markdown.replace(
            /```(\w*)\n?([\s\S]*?)```/g,
            (_match: string, language: string, code: string): string => {
                const languageClass = language ? ` class="language-${language}"` : "";
                this.#preAndCodeBlocks.push(`<pre><code${languageClass}>${this.#getEscapedHtml(code)}</code></pre>`);
                return this.#getPlaceholder(CODE_BLOCK_PLACEHOLDER, this.#preAndCodeBlocks.length - 1);
            }
        );

        return markdown.replace(
            /`([^`\n]+)`/g,
            (_match: string, code: string): string => {
                this.#inlineCodeBlocks.push(`<code>${this.#getEscapedHtml(code)}</code>`);
                return this.#getPlaceholder(INLINE_CODE_PLACEHOLDER, this.#inlineCodeBlocks.length - 1);
            }
        );
    }

    #getPlaceholder(placeholder: string, index: number): string {
        return placeholder.replace("%d", String(index));
    }

    #getEscapedHtml(code: string): string {
        return HtmlEscaper.getInstance().getEscapedHtml(code);
    }

    #getImgTags(html: string): string {
        return html.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, "<img alt=\"$1\" src=\"$2\">");
    }

    #getATags(html: string): string {
        return html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, "<a href=\"$2\">$1</a>");
    }

    #getHTags(html: string): string {
        return html.replace(
            /^(#{1,6})\s+(.*)$/gm,
            (_match: string, hashes: string, title: string): string => {
                const level = hashes.length;
                return `<h${level} id="${this.#getAnchorId(title)}">${title}</h${level}>`;
            }
        );
    }

    /**
     * "2.1 Alta de usuario" -> "2-1-alta-de-usuario"; los puntos de una version se mantienen ("1.0.0").
     */
    #getAnchorId(title: string): string {
        let anchorId = title.replace(/<[^>]*>/g, "").toLowerCase();
        anchorId = anchorId.replace(/[^\p{L}\p{N}.]+/gu, "-");
        return anchorId.replace(/^[-.]+|[-.]+$/g, "");
    }

    #getTextFormattingTags(html: string): string {
        const patterns: [RegExp, string][] = [
            [/\*\*\*([^*\n]+)\*\*\*/g, "<strong><em>$1</em></strong>"],
            [/___([^_\n]+)___/g, "<strong><em>$1</em></strong>"],
            [/\*\*([^*\n]+)\*\*/g, "<strong>$1</strong>"],
            [/(?<!_)__([^_\n]+)__(?!_)/g, "<strong>$1</strong>"],
            [/(?<!\*)\*([^*\n]+)\*(?!\*)/g, "<em>$1</em>"],
            // cursiva con _ solo entre espacios/bordes: "user_uuid" o "api_token" no son cursiva
            [/(?<![\w_])_([^_\n]+)_(?![\w_])/g, "<em>$1</em>"],
            [/~~([^~\n]+)~~/g, "<del>$1</del>"],
        ];
        for (const [pattern, replacement] of patterns) {
            html = html.replace(pattern, replacement);
        }
        return html;
    }

    #getBlockquoteTags(html: string): string {
        return html.replace(/^> (.*)$/gm, "<blockquote>$1</blockquote>");
    }

    #getHrTags(html: string): string {
        return html.replace(/^(---|\*\*\*|___)\s*$/gm, "<hr>");
    }

    #getListTags(html: string): string {
        const listLines: string[] = [];
        let openListTag = "";
        for (const line of html.split("\n")) {
            let listTag = "";
            let matches = /^\s*[-*+]\s+(.*)$/.exec(line);
            if (matches) {
                listTag = "ul";
            }
            else {
                matches = /^\s*\d+\.\s+(.*)$/.exec(line);
                if (matches) listTag = "ol";
            }

            if (openListTag && listTag !== openListTag) {
                listLines.push(`</${openListTag}>`);
                openListTag = "";
            }
            if (!listTag || !matches) {
                if (line.trim() !== "") listLines.push(line);
                continue;
            }
            if (!openListTag) {
                listLines.push(`<${listTag}>`);
                openListTag = listTag;
            }
            listLines.push(`<li>${matches[1]}</li>`);
        }
        if (openListTag) listLines.push(`</${openListTag}>`);

        return listLines.join("\n");
    }

    #getWithCodeRestored(html: string): string {
        this.#inlineCodeBlocks.forEach((inlineCodeBlock, index) => {
            html = html.replaceAll(this.#getPlaceholder(INLINE_CODE_PLACEHOLDER, index), inlineCodeBlock);
        });
        this.#preAndCodeBlocks.forEach((preAndCodeBlock, index) => {
            html = html.replaceAll(this.#getPlaceholder(CODE_BLOCK_PLACEHOLDER, index), preAndCodeBlock);
        });
        return html;
    }

    /**
     * Tabla = fila "| a | b |" seguida de una separadora "|---|:--:|" (que fija la alineacion).
     */
    #getTableTags(html: string): string {
        const lines = html.split("\n");
        const tableLines: string[] = [];
        let i = 0;
        while (i < lines.length) {
            const line = lines[i].trim();
            const isTableStart = line.startsWith("|")
                && i + 1 < lines.length
                && /^\|[\s:|-]+\|$/.test(lines[i + 1].trim());
            if (!isTableStart) {
                tableLines.push(lines[i]);
                i++;
                continue;
            }

            const alignments = this.#getTableAlignments(lines[i + 1].trim());
            let tableHtml = "<table class=\"md-table\">\n<thead>\n<tr>"
                + this.#getTableCells(line, "th", alignments)
                + "</tr>\n</thead>\n<tbody>";
            i += 2;
            while (i < lines.length && lines[i].trim().startsWith("|")) {
                tableHtml += `\n<tr>${this.#getTableCells(lines[i].trim(), "td", alignments)}</tr>`;
                i++;
            }
            tableLines.push(`${tableHtml}\n</tbody>\n</table>`);
        }
        return tableLines.join("\n");
    }

    #getTableCells(row: string, cellTag: string, alignments: string[]): string {
        let cellsHtml = "";
        this.#getTableRowCells(row).forEach((cell, index) => {
            const style = alignments[index] ? ` style="text-align:${alignments[index]}"` : "";
            cellsHtml += `<${cellTag}${style}>${cell.trim()}</${cellTag}>`;
        });
        return cellsHtml;
    }

    #getTableRowCells(row: string): string[] {
        return row.trim().replace(/^\|+|\|+$/g, "").split("|");
    }

    #getTableAlignments(separatorRow: string): string[] {
        return this.#getTableRowCells(separatorRow).map((cell: string): string => {
            cell = cell.trim();
            const isLeft = cell.startsWith(":");
            const isRight = cell.endsWith(":");
            if (isLeft && isRight) return "center";
            if (isRight) return "right";
            if (isLeft) return "left";
            return "";
        });
    }

    #getPTags(html: string): string {
        const paragraphs = html
            .replace(/\n\n+/g, "\n\n")
            .split("\n\n")
            .filter((paragraph) => paragraph.trim() !== "");

        return paragraphs.map((paragraph: string): string => {
            paragraph = paragraph.trim();
            for (const blockTagPrefix of BLOCK_TAG_PREFIXES) {
                if (paragraph.startsWith(blockTagPrefix)) return paragraph;
            }
            if (paragraph.includes("<table") || paragraph.includes("<pre><code")) return paragraph;

            return `<p>${paragraph.replaceAll("\n", "<br>")}</p>`;
        }).join("\n");
    }
}
