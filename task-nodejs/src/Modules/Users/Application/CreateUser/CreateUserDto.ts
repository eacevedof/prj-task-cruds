import { AbstractInputDto } from "#app/Modules/Shared/Application/AbstractInputDto.ts";
import { getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class CreateUserDto extends AbstractInputDto {

    readonly #userName: string;
    readonly #userEmail: string;
    readonly #password: string;

    constructor(primitives: Primitives) {
        super(primitives);

        this.#userName = getTrimmedString(primitives.userName);
        this.#userEmail = getTrimmedString(primitives.userEmail);
        this.#password = getTrimmedString(primitives.password);
    }

    static fromPrimitives(primitives: Primitives): CreateUserDto {
        return new CreateUserDto(primitives);
    }

    getUserName(): string {
        return this.#userName;
    }

    getUserEmail(): string {
        return this.#userEmail;
    }

    getPassword(): string {
        return this.#password;
    }
}
