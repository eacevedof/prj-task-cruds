import { AbstractInputDto } from "#app/Modules/Shared/Application/AbstractInputDto.ts";
import { getInt, getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class DeleteUserDto extends AbstractInputDto {

    readonly #authUserId: number;
    readonly #authUserUuid: string;
    readonly #userUuid: string;

    constructor(primitives: Primitives) {
        super(primitives);

        this.#authUserId = getInt(primitives.authUserId ?? 0);
        this.#authUserUuid = getTrimmedString(primitives.authUserUuid);
        this.#userUuid = getTrimmedString(primitives.userUuid);
    }

    static fromPrimitives(primitives: Primitives): DeleteUserDto {
        return new DeleteUserDto(primitives);
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
}
