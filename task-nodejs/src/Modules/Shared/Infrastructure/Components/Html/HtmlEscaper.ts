const HTML_ENTITIES: Record<string, string> = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    "\"": "&quot;",
    "'": "&#039;",
};

/**
 * El htmlspecialchars() de PHP (ENT_QUOTES): lo que se pinta dentro de un html sin que se interprete como html.
 */
export class HtmlEscaper {

    static getInstance(): HtmlEscaper {
        return new HtmlEscaper();
    }

    getEscapedHtml(text: string): string {
        return text.replace(/[&<>"']/g, (character) => HTML_ENTITIES[character]);
    }
}
