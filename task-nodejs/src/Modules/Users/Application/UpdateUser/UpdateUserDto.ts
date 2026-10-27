import { AbstractInputDto } from "#app/Modules/Shared/Application/AbstractInputDto.ts";
import { getInt, getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class UpdateUserDto extends AbstractInputDto {

    readonly #authUserId: number;
    readonly #authUserUuid: string;
    readonly #userUuid: string;
    readonly #userName: string;
    readonly #userEmail: string;

    constructor(primitives: Primitives) {
        super(primitives);

        this.#authUserId = getInt(primitives.authUserId ?? 0);
        this.#authUserUuid = getTrimmedString(primitives.authUserUuid);
        this.#userUuid = getTrimmedString(primitives.userUuid);
        this.#userName = getTrimmedString(primitives.userName);
        this.#userEmail = getTrimmedString(primitives.userEmail);
    }

    static fromPrimitives(primitives: Primitives): UpdateUserDto {
        return new UpdateUserDto(primitives);
    }

    getAuthUserId(): number {
        return this.#authUserId;
    }

    getAuthUserUuid(): string {
        return this.#authUserUuid;
    }

    getUserUuid(): string {
        return this.#userUuid;
    }

    getUserName(): string {
        return this.#userName;
    }

    getUserEmail(): string {
        return this.#userEmail;
    }
}
