import { readFileSync } from "node:fs";

import { Markdowner } from "#app/Modules/Shared/Infrastructure/Components/Markdown/Markdowner.ts";
import { AbstractView } from "#app/Modules/Shared/Infrastructure/Views/AbstractView.ts";

import type { DocumentationViewDto } from "#app/Modules/Open/Documentation/Infrastructure/Views/DocumentationViewDto.ts";

const TITLE = "Task Node.js CRUD API";

/**
 * Publica documentation.md: TODA la documentacion de la API vive en ese .md (aqui no hay texto).
 */
export class DocumentationView extends AbstractView {

    readonly #markdowner: Markdowner;

    constructor() {
        super();
        this.#markdowner = Markdowner.getInstance();
    }

    static getInstance(): DocumentationView {
        return new DocumentationView();
    }

    getHtml(documentationViewDto: DocumentationViewDto): string {
        const markdown = this.getReplacedContent(readFileSync(new URL("documentation.md", import.meta.url), "utf8"), {
            "{{appVersion}}": documentationViewDto.getAppVersion(),
            "{{appVersionUpdate}}": documentationViewDto.getAppVersionUpdate(),
            "{{appBaseUrl}}": documentationViewDto.getAppBaseUrl(),
        });
        return this.getHtmlPage(TITLE, this.#markdowner.getHtmlFromMarkdown(markdown));
    }
}
