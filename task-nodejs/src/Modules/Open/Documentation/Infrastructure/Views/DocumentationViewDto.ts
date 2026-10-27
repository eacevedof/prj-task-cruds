import { getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class DocumentationViewDto {

    readonly #appVersion: string;
    readonly #appVersionUpdate: string;
    readonly #appBaseUrl: string;
    readonly #errors: string[];

    constructor(appVersion: string, appVersionUpdate: string, appBaseUrl: string, errors: string[]) {
        this.#appVersion = appVersion;
        this.#appVersionUpdate = appVersionUpdate;
        this.#appBaseUrl = appBaseUrl;
        this.#errors = errors;
    }

    static fromPrimitives(primitives: Primitives): DocumentationViewDto {
        return new DocumentationViewDto(
            getTrimmedString(primitives.appVersion),
            getTrimmedString(primitives.appVersionUpdate),
            getTrimmedString(primitives.appBaseUrl),
            Array.isArray(primitives.errors) ? primitives.errors : [],
        );
    }

    getAppVersion(): string {
        return this.#appVersion;
    }

    getAppVersionUpdate(): string {
        return this.#appVersionUpdate;
    }

    getAppBaseUrl(): string {
        return this.#appBaseUrl;
    }

    getErrors(): string[] {
        return this.#errors;
    }
}
