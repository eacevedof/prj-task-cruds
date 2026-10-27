import { getInt, getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

/**
 * userId es el que usan todas las queries (mas ligero que el uuid); userUuid solo para comparar con el
 * user_uuid que llega de fuera (argumento GraphQL, opcion de consola).
 */
export class AuthenticateUserResultDto {

    readonly #userId: number;
    readonly #userUuid: string;

    constructor(userId: number, userUuid: string) {
        this.#userId = userId;
        this.#userUuid = userUuid;
    }

    static fromPrimitives(primitives: Primitives): AuthenticateUserResultDto {
        return new AuthenticateUserResultDto(
            getInt(primitives.userId ?? 0),
            getTrimmedString(primitives.userUuid),
        );
    }

    getUserId(): number {
        return this.#userId;
    }

    getUserUuid(): string {
        return this.#userUuid;
    }

    toAuthPrimitives(): Primitives {
        return {
            authUserId: this.#userId,
            authUserUuid: this.#userUuid,
        };
    }
}
