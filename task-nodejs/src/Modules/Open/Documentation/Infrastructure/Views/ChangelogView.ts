import { readFileSync } from "node:fs";

import { Markdowner } from "#app/Modules/Shared/Infrastructure/Components/Markdown/Markdowner.ts";
import { AbstractView } from "#app/Modules/Shared/Infrastructure/Views/AbstractView.ts";

import type { DocumentationViewDto } from "#app/Modules/Open/Documentation/Infrastructure/Views/DocumentationViewDto.ts";

const TITLE = "Task Node.js CRUD API - changelog";

/**
 * Publica changelog.md (una seccion por version; el titulo de la doc enlaza a #<version>).
 */
export class ChangelogView extends AbstractView {

    readonly #markdowner: Markdowner;

    constructor() {
        super();
        this.#markdowner = Markdowner.getInstance();
    }

    static getInstance(): ChangelogView {
        return new ChangelogView();
    }

    getHtml(documentationViewDto: DocumentationViewDto): string {
        const markdown = this.getReplacedContent(readFileSync(new URL("changelog.md", import.meta.url), "utf8"), {
            "{{appBaseUrl}}": documentationViewDto.getAppBaseUrl(),
        });
        return this.getHtmlPage(TITLE, this.#markdowner.getHtmlFromMarkdown(markdown));
    }
}
