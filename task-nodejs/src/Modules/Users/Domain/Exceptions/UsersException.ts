import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import type { InterfaceException } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

export class UsersException extends Error implements InterfaceException {

    readonly #code: number;

    constructor(message: string, code: number) {
        super(message);
        this.name = "UsersException";
        this.#code = code;
    }

    /**
     * @throws UsersException
     */
    static unauthorizedCustom(message: string): never {
        throw new UsersException(message, ExceptionEnum.CODE_UNAUTHORIZED);
    }

    /**
     * @throws UsersException
     */
    static notFoundCustom(message: string): never {
        throw new UsersException(message, ExceptionEnum.CODE_NOT_FOUND);
    }

    /**
     * @throws UsersException
     */
    static conflictCustom(message: string): never {
        throw new UsersException(message, ExceptionEnum.CODE_CONFLICT);
    }

    getCode(): number {
        return this.#code;
    }
}
