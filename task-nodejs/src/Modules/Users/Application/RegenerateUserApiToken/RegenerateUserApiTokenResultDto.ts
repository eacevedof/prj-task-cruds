import { getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";

export class RegenerateUserApiTokenResultDto {

    readonly #userUuid: string;
    readonly #apiToken: string;

    constructor(userUuid: string, apiToken: string) {
        this.#userUuid = userUuid;
        this.#apiToken = apiToken;
    }

    static fromPrimitives(primitives: Primitives): RegenerateUserApiTokenResultDto {
        return new RegenerateUserApiTokenResultDto(
            getTrimmedString(primitives.userUuid),
            getTrimmedString(primitives.apiToken),
        );
    }

    getUserUuid(): string {
        return this.#userUuid;
    }

    getApiToken(): string {
        return this.#apiToken;
    }

    toArray(): Primitives {
        return {
            [UsersKeyEnum.USER_UUID]: this.#userUuid,
            [UsersKeyEnum.API_TOKEN]: this.#apiToken,
        };
    }
}
