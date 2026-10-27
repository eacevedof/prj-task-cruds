import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";

import type { InterfaceException } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

export class FieldsException extends Error implements InterfaceException {

    readonly #code: number;

    constructor(message: string) {
        super(message);
        this.name = "FieldsException";
        this.#code = ExceptionEnum.CODE_BAD_REQUEST;
    }

    /**
     * @throws FieldsException
     */
    static badRequestCustom(message: string): never {
        throw new FieldsException(message);
    }

    getCode(): number {
        return this.#code;
    }
}
