import { getPrimitives, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class GetUserResultDto {

    // el usuario (UserEntity.toPrimitives)
    readonly #user: Primitives;

    constructor(primitives: Primitives) {
        this.#user = getPrimitives(primitives.user);
    }

    static fromPrimitives(primitives: Primitives): GetUserResultDto {
        return new GetUserResultDto(primitives);
    }

    getUser(): Primitives {
        return this.#user;
    }

    toArray(): Primitives {
        return this.#user;
    }
}
