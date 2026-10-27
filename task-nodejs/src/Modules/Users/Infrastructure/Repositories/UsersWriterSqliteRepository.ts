import { AbstractSqliteRepository } from "#app/Modules/Shared/Infrastructure/Repositories/AbstractSqliteRepository.ts";

import type { UserEntity } from "#app/Modules/Users/Domain/Entities/UserEntity.ts";

export class UsersWriterSqliteRepository extends AbstractSqliteRepository {

    static getInstance(): UsersWriterSqliteRepository {
        return new UsersWriterSqliteRepository();
    }

    /**
     * En el alta updated_at = created_at.
     */
    insertUser(userEntity: UserEntity): number {
        const sql = `
        -- insertUser
        INSERT INTO \`users\`
            (user_uuid, user_name, user_email, password_hash, api_token_hash, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?)
        `;
        this.execute(sql, [
            userEntity.getUserUuid(),
            userEntity.getUserName(),
            userEntity.getUserEmail(),
            userEntity.getPasswordHash(),
            userEntity.getApiTokenHash(),
            userEntity.getCreatedAt(),
            userEntity.getCreatedAt(),
        ]);
        return this.lastInsertId();
    }

    updateUserById(userEntity: UserEntity): number {
        const sql = `
        -- updateUserById
        UPDATE \`users\`
        SET user_name = ?, user_email = ?, updated_at = ?
        WHERE 1=1
        AND id = ${userEntity.getId()}
        `;
        return this.execute(sql, [
            userEntity.getUserName(),
            userEntity.getUserEmail(),
            userEntity.getUpdatedAt(),
        ]);
    }

    /**
     * El token anterior deja de valer en el acto: solo existe el hash nuevo.
     */
    updateApiTokenHashById(userEntity: UserEntity): number {
        const sql = `
        -- updateApiTokenHashById
        UPDATE \`users\`
        SET api_token_hash = ?, updated_at = ?
        WHERE 1=1
        AND id = ${userEntity.getId()}
        `;
        return this.execute(sql, [userEntity.getApiTokenHash(), userEntity.getUpdatedAt()]);
    }

    /**
     * Rehash en el login cuando cambia el algoritmo o el coste del hash.
     */
    updatePasswordHashById(userEntity: UserEntity): number {
        const sql = `
        -- updatePasswordHashById
        UPDATE \`users\`
        SET password_hash = ?, updated_at = ?
        WHERE 1=1
        AND id = ${userEntity.getId()}
        `;
        return this.execute(sql, [userEntity.getPasswordHash(), userEntity.getUpdatedAt()]);
    }

    /**
     * Borra tambien sus tasks (FK ON DELETE CASCADE).
     */
    deleteUserById(userEntity: UserEntity): number {
        const sql = `
        -- deleteUserById
        DELETE FROM \`users\`
        WHERE 1=1
        AND id = ${userEntity.getId()}
        `;
        return this.execute(sql);
    }
}
