import { getInt, getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

import { UsersKeyEnum } from "#app/Modules/Users/Domain/Enums/UsersKeyEnum.ts";

export class UserEntity {

    static readonly USER_UUID_PREFIX = "user-";

    readonly #id: number | null;
    readonly #userUuid: string;
    readonly #userName: string;
    readonly #userEmail: string;
    readonly #passwordHash: string;
    readonly #apiTokenHash: string;
    readonly #createdAt: string;
    readonly #updatedAt: string;

    /**
     * Desde una fila de la tabla users (columnas en snake_case: contrato con la BD). Las lecturas no traen
     * password_hash ni api_token_hash: solo los informan las escrituras.
     */
    constructor(primitives: Primitives) {
        this.#id = primitives[UsersKeyEnum.ID] != null ? getInt(primitives[UsersKeyEnum.ID]) : null;
        this.#userUuid = getTrimmedString(primitives[UsersKeyEnum.USER_UUID]);
        this.#userName = getTrimmedString(primitives[UsersKeyEnum.USER_NAME]);
        this.#userEmail = getTrimmedString(primitives[UsersKeyEnum.USER_EMAIL]);
        this.#passwordHash = getTrimmedString(primitives[UsersKeyEnum.PASSWORD_HASH]);
        this.#apiTokenHash = getTrimmedString(primitives[UsersKeyEnum.API_TOKEN_HASH]);
        this.#createdAt = getTrimmedString(primitives[UsersKeyEnum.CREATED_AT]);
        this.#updatedAt = getTrimmedString(primitives[UsersKeyEnum.UPDATED_AT]);
    }

    static fromPrimitives(primitives: Primitives): UserEntity {
        return new UserEntity(primitives);
    }

    /**
     * Representacion publica (API): el id interno no sale, el identificador es user_uuid. Los hashes nunca.
     */
    toPrimitives(): Primitives {
        return {
            [UsersKeyEnum.USER_UUID]: this.#userUuid,
            [UsersKeyEnum.USER_NAME]: this.#userName,
            [UsersKeyEnum.USER_EMAIL]: this.#userEmail,
            [UsersKeyEnum.CREATED_AT]: this.#createdAt,
            [UsersKeyEnum.UPDATED_AT]: this.#updatedAt,
        };
    }

    getId(): number | null {
        return this.#id;
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

    getPasswordHash(): string {
        return this.#passwordHash;
    }

    getApiTokenHash(): string {
        return this.#apiTokenHash;
    }

    getCreatedAt(): string {
        return this.#createdAt;
    }

    getUpdatedAt(): string {
        return this.#updatedAt;
    }
}
