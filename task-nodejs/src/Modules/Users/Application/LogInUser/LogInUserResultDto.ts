import { getPrimitives, getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";

export class LogInUserResultDto {

    // el usuario (UserEntity.toPrimitives) + su api_token nuevo en claro, solo aqui
    readonly #user: Primitives;
    readonly #apiToken: string;

    constructor(primitives: Primitives) {
        this.#user = getPrimitives(primitives.user);
        this.#apiToken = getTrimmedString(primitives.apiToken);
    }

    static fromPrimitives(primitives: Primitives): LogInUserResultDto {
        return new LogInUserResultDto(primitives);
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
