import { AbstractInputDto } from "#app/Modules/Shared/Application/AbstractInputDto.ts";
import { getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class AuthenticateConsoleUserDto extends AbstractInputDto {

    readonly #userUuid: string;

    constructor(primitives: Primitives) {
        super(primitives);

        this.#userUuid = getTrimmedString(primitives.userUuid);
    }

    static fromPrimitives(primitives: Primitives): AuthenticateConsoleUserDto {
        return new AuthenticateConsoleUserDto(primitives);
    }

    getUserUuid(): string {
        return this.#userUuid;
    }
}
