import { getTrimmedString } from "#app/Modules/Shared/Application/Primitives.ts";
import { AbstractSqliteRepository } from "#app/Modules/Shared/Infrastructure/Repositories/AbstractSqliteRepository.ts";

import { UserEntity } from "#app/Modules/Users/Domain/Entities/UserEntity.ts";

export class UsersReaderSqliteRepository extends AbstractSqliteRepository {

    static getInstance(): UsersReaderSqliteRepository {
        return new UsersReaderSqliteRepository();
    }

    getUserById(userId: number): UserEntity | null {
        const sql = `
        -- getUserById
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM \`users\`
        WHERE 1=1
        AND id = ${userId}
        `;
        const rows = this.query(sql);
        return rows.length ? UserEntity.fromPrimitives(rows[0]) : null;
    }

    getUserByUserUuid(userUuid: string): UserEntity | null {
        const sql = `
        -- getUserByUserUuid
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM \`users\`
        WHERE 1=1
        AND user_uuid = ?
        `;
        const rows = this.query(sql, [userUuid]);
        return rows.length ? UserEntity.fromPrimitives(rows[0]) : null;
    }

    /**
     * Autenticacion: el token llega en claro y aqui solo se compara su hash (api_token_hash es UNIQUE).
     */
    getUserByApiTokenHash(apiTokenHash: string): UserEntity | null {
        const sql = `
        -- getUserByApiTokenHash
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM \`users\`
        WHERE 1=1
        AND api_token_hash = ?
        `;
        const rows = this.query(sql, [apiTokenHash]);
        return rows.length ? UserEntity.fromPrimitives(rows[0]) : null;
    }

    /**
     * Login: user_name es unico sin distinguir mayusculas (indice uk_users_user_name COLLATE NOCASE).
     */
    getUserByUserName(userName: string): UserEntity | null {
        const sql = `
        -- getUserByUserName
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM \`users\`
        WHERE 1=1
        AND user_name = ? COLLATE NOCASE
        `;
        const rows = this.query(sql, [userName]);
        return rows.length ? UserEntity.fromPrimitives(rows[0]) : null;
    }

    /**
     * Fuera de UserEntity a proposito: el hash solo lo necesita el login y nunca debe acabar en una respuesta.
     * "" = cuenta sin password (creada antes de 20261025101838-add-password-hash-to-users.sql).
     */
    getPasswordHashByUserId(userId: number): string {
        const sql = `
        -- getPasswordHashByUserId
        SELECT
            password_hash
        FROM \`users\`
        WHERE 1=1
        AND id = ${userId}
        `;
        const rows = this.query(sql);
        return getTrimmedString(rows[0]?.password_hash);
    }

    /**
     * Unicidad del user_name en el alta (sin distinguir mayusculas, como el indice).
     */
    doesUserNameExist(userName: string): boolean {
        const sql = `
        -- doesUserNameExist
        SELECT
            COUNT(*) AS n_rows
        FROM \`users\`
        WHERE 1=1
        AND user_name = ? COLLATE NOCASE
        `;
        const rows = this.query(sql, [userName]);
        return Boolean(rows[0]?.n_rows ?? 0);
    }

    /**
     * Unicidad del user_name en la modificacion: el del propio usuario no cuenta como repetido.
     */
    doesUserNameExistInOtherUser(userEntity: UserEntity): boolean {
        const sql = `
        -- doesUserNameExistInOtherUser
        SELECT
            COUNT(*) AS n_rows
        FROM \`users\`
        WHERE 1=1
        AND user_name = ? COLLATE NOCASE
        AND id != ${userEntity.getId()}
        `;
        const rows = this.query(sql, [userEntity.getUserName()]);
        return Boolean(rows[0]?.n_rows ?? 0);
    }

    /**
     * Unicidad del email en el alta (sin distinguir mayusculas: la columna es COLLATE NOCASE).
     */
    doesUserEmailExist(userEmail: string): boolean {
        const sql = `
        -- doesUserEmailExist
        SELECT
            COUNT(*) AS n_rows
        FROM \`users\`
        WHERE 1=1
        AND user_email = ?
        `;
        const rows = this.query(sql, [userEmail]);
        return Boolean(rows[0]?.n_rows ?? 0);
    }

    /**
     * Unicidad del email en la modificacion: el email del propio usuario no cuenta como repetido.
     */
    doesUserEmailExistInOtherUser(userEntity: UserEntity): boolean {
        const sql = `
        -- doesUserEmailExistInOtherUser
        SELECT
            COUNT(*) AS n_rows
        FROM \`users\`
        WHERE 1=1
        AND user_email = ?
        AND id != ${userEntity.getId()}
        `;
        const rows = this.query(sql, [userEntity.getUserEmail()]);
        return Boolean(rows[0]?.n_rows ?? 0);
    }
}
