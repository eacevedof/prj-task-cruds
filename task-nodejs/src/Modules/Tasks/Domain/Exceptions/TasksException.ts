import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import type { InterfaceException } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

export class TasksException extends Error implements InterfaceException {

    readonly #code: number;

    constructor(message: string, code: number) {
        super(message);
        this.name = "TasksException";
        this.#code = code;
    }

    /**
     * @throws TasksException
     */
    static unauthorizedCustom(message: string): never {
        throw new TasksException(message, ExceptionEnum.CODE_UNAUTHORIZED);
    }

    /**
     * @throws TasksException
     */
    static notFoundCustom(message: string): never {
        throw new TasksException(message, ExceptionEnum.CODE_NOT_FOUND);
    }

    getCode(): number {
        return this.#code;
    }
}
