import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

export class ListUsersResultDto {

    // lista de UserEntity.toPrimitives
    readonly #users: Primitives[];

    constructor(primitives: Primitives) {
        this.#users = Array.isArray(primitives.users) ? primitives.users : [];
    }

    static fromPrimitives(primitives: Primitives): ListUsersResultDto {
        return new ListUsersResultDto(primitives);
    }

    getUsers(): Primitives[] {
        return this.#users;
    }

    toArray(): Primitives[] {
        return this.#users;
    }
}
