import { getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class SendAlertEmailDto {

    readonly #alertType: string;
    readonly #subject: string;
    readonly #details: string;

    /**
     * primitives: alertType (AlertEmailTypeEnum), subject, details (texto plano: el servicio lo escapa)
     */
    constructor(primitives: Primitives) {
        this.#alertType = getTrimmedString(primitives.alertType);
        this.#subject = getTrimmedString(primitives.subject);
        this.#details = getTrimmedString(primitives.details);
    }

    static fromPrimitives(primitives: Primitives): SendAlertEmailDto {
        return new SendAlertEmailDto(primitives);
    }

    getAlertType(): string {
        return this.#alertType;
    }

    getSubject(): string {
        return this.#subject;
    }

    getDetails(): string {
        return this.#details;
    }
}
