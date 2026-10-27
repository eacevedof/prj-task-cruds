import { getPrimitives, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class UpdateUserResultDto {

    // el usuario modificado (UserEntity.toPrimitives)
    readonly #user: Primitives;

    constructor(primitives: Primitives) {
        this.#user = getPrimitives(primitives.user);
    }

    static fromPrimitives(primitives: Primitives): UpdateUserResultDto {
        return new UpdateUserResultDto(primitives);
    }

    getUser(): Primitives {
        return this.#user;
    }

    toArray(): Primitives {
        return this.#user;
    }
}
