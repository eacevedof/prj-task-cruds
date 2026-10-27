import { getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class RunMigrationsResultDto {

    // traza de lo ocurrido, en orden: la imprime el command (el servicio no escribe en stdout)
    readonly #messages: string[];

    constructor(primitives: Primitives) {
        this.#messages = (Array.isArray(primitives.messages) ? primitives.messages : [])
            .map((message: unknown): string => getTrimmedString(message));
    }

    static fromPrimitives(primitives: Primitives): RunMigrationsResultDto {
        return new RunMigrationsResultDto(primitives);
    }

    getMessages(): string[] {
        return this.#messages;
    }
}
