import { getInt, getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";

export class DeleteUserResultDto {

    readonly #userUuid: string;
    readonly #affected: number;

    constructor(primitives: Primitives) {
        this.#userUuid = getTrimmedString(primitives.userUuid);
        this.#affected = getInt(primitives.affected ?? 0);
    }

    static fromPrimitives(primitives: Primitives): DeleteUserResultDto {
        return new DeleteUserResultDto(primitives);
    }

    getUserUuid(): string {
        return this.#userUuid;
    }

    getAffected(): number {
        return this.#affected;
    }

    toArray(): Primitives {
        return {
            [UsersKeyEnum.USER_UUID]: this.#userUuid,
            [UsersKeyEnum.AFFECTED]: this.#affected,
        };
    }
}
