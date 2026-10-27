import { AbstractInputDto } from "#app/Modules/Shared/Application/AbstractInputDto.ts";
import { getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class LogInUserDto extends AbstractInputDto {

    readonly #userName: string;
    readonly #password: string;

    constructor(primitives: Primitives) {
        super(primitives);

        this.#userName = getTrimmedString(primitives.userName);
        this.#password = getTrimmedString(primitives.password);
    }

    static fromPrimitives(primitives: Primitives): LogInUserDto {
        return new LogInUserDto(primitives);
    }

    getUserName(): string {
        return this.#userName;
    }

    getPassword(): string {
        return this.#password;
    }
}
