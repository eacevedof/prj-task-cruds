package modules.users.infrastructure.repositories;

import modules.shared.infrastructure.repositories.AbstractSqliteRepository;
import modules.users.domain.entities.UserEntity;

public final class UsersWriterSqliteRepository extends AbstractSqliteRepository {

    public static UsersWriterSqliteRepository getInstance() {
        return new UsersWriterSqliteRepository();
    }

    /**
     * En el alta updated_at = created_at.
     */
    public int insertUser(UserEntity userEntity) {
        String sql = """
            -- insertUser
            INSERT INTO `users`
                (user_uuid, user_name, user_email, password_hash, api_token_hash, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?)
            """;
        execute(
            sql,
            userEntity.getUserUuid(),
            userEntity.getUserName(),
            userEntity.getUserEmail(),
            userEntity.getPasswordHash(),
            userEntity.getApiTokenHash(),
            userEntity.getCreatedAt(),
            userEntity.getCreatedAt()
        );
        return lastInsertId();
    }

    public int updateUserById(UserEntity userEntity) {
        String sql = """
            -- updateUserById
            UPDATE `users`
            SET user_name = ?, user_email = ?, updated_at = ?
            WHERE 1=1
            AND id = %d
            """.formatted(userEntity.getId());
        return execute(sql, userEntity.getUserName(), userEntity.getUserEmail(), userEntity.getUpdatedAt());
    }

    /**
     * El token anterior deja de valer en el acto: solo existe el hash nuevo.
     */
    public int updateApiTokenHashById(UserEntity userEntity) {
        String sql = """
            -- updateApiTokenHashById
            UPDATE `users`
            SET api_token_hash = ?, updated_at = ?
            WHERE 1=1
            AND id = %d
            """.formatted(userEntity.getId());
        return execute(sql, userEntity.getApiTokenHash(), userEntity.getUpdatedAt());
    }

    /**
     * Rehash en el login cuando cambia el algoritmo o el coste del hash.
     */
    public int updatePasswordHashById(UserEntity userEntity) {
        String sql = """
            -- updatePasswordHashById
            UPDATE `users`
            SET password_hash = ?, updated_at = ?
            WHERE 1=1
            AND id = %d
            """.formatted(userEntity.getId());
        return execute(sql, userEntity.getPasswordHash(), userEntity.getUpdatedAt());
    }

    /**
     * Borra tambien sus tasks (FK ON DELETE CASCADE).
     */
    public int deleteUserById(UserEntity userEntity) {
        String sql = """
            -- deleteUserById
            DELETE FROM `users`
            WHERE 1=1
            AND id = %d
            """.formatted(userEntity.getId());
        return execute(sql);
    }
}
