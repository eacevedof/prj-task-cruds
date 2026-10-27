import { getPrimitives, getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";

export class CreateUserResultDto {

    // el usuario creado (UserEntity.toPrimitives) + su api_token en claro, solo aqui
    readonly #user: Primitives;
    readonly #apiToken: string;

    constructor(primitives: Primitives) {
        this.#user = getPrimitives(primitives.user);
        this.#apiToken = getTrimmedString(primitives.apiToken);
    }

    static fromPrimitives(primitives: Primitives): CreateUserResultDto {
        return new CreateUserResultDto(primitives);
    }

    getUser(): Primitives {
        return this.#user;
    }

    getApiToken(): string {
        return this.#apiToken;
    }

    toArray(): Primitives {
        return {
            ...this.#user,
            [UsersKeyEnum.API_TOKEN]: this.#apiToken,
        };
    }
}
