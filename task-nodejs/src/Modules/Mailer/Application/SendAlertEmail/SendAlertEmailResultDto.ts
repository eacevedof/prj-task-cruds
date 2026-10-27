import { getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class SendAlertEmailResultDto {

    // false sin error: APP_EMAIL_TO vacio = alertas por email desactivadas (p.ej. en los tests)
    readonly #isSent: boolean;
    readonly #emailTo: string;

    constructor(primitives: Primitives) {
        this.#isSent = Boolean(primitives.isSent ?? false);
        this.#emailTo = getTrimmedString(primitives.emailTo);
    }

    static fromPrimitives(primitives: Primitives): SendAlertEmailResultDto {
        return new SendAlertEmailResultDto(primitives);
    }

    isSent(): boolean {
        return this.#isSent;
    }

    getEmailTo(): string {
        return this.#emailTo;
    }
}
