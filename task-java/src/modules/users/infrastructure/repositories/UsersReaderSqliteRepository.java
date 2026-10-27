package modules.users.infrastructure.repositories;

import java.util.List;
import java.util.Map;

import modules.shared.infrastructure.repositories.AbstractSqliteRepository;
import modules.users.domain.entities.UserEntity;

import static modules.shared.application.Primitives.getInt;
import static modules.shared.application.Primitives.getTrimmedString;

public final class UsersReaderSqliteRepository extends AbstractSqliteRepository {

    public static UsersReaderSqliteRepository getInstance() {
        return new UsersReaderSqliteRepository();
    }

    public UserEntity getUserById(int userId) {
        String sql = """
            -- getUserById
            SELECT
                id, user_uuid, user_name, user_email, created_at, updated_at
            FROM `users`
            WHERE 1=1
            AND id = %d
            """.formatted(userId);
        List<Map<String, Object>> rows = query(sql);
        return rows.isEmpty() ? null : UserEntity.fromPrimitives(rows.getFirst());
    }

    public UserEntity getUserByUserUuid(String userUuid) {
        String sql = """
            -- getUserByUserUuid
            SELECT
                id, user_uuid, user_name, user_email, created_at, updated_at
            FROM `users`
            WHERE 1=1
            AND user_uuid = ?
            """;
        List<Map<String, Object>> rows = query(sql, userUuid);
        return rows.isEmpty() ? null : UserEntity.fromPrimitives(rows.getFirst());
    }

    /**
     * Autenticacion: el token llega en claro y aqui solo se compara su hash (api_token_hash es UNIQUE).
     */
    public UserEntity getUserByApiTokenHash(String apiTokenHash) {
        String sql = """
            -- getUserByApiTokenHash
            SELECT
                id, user_uuid, user_name, user_email, created_at, updated_at
            FROM `users`
            WHERE 1=1
            AND api_token_hash = ?
            """;
        List<Map<String, Object>> rows = query(sql, apiTokenHash);
        return rows.isEmpty() ? null : UserEntity.fromPrimitives(rows.getFirst());
    }

    /**
     * Login: user_name es unico sin distinguir mayusculas (indice uk_users_user_name COLLATE NOCASE).
     */
    public UserEntity getUserByUserName(String userName) {
        String sql = """
            -- getUserByUserName
            SELECT
                id, user_uuid, user_name, user_email, created_at, updated_at
            FROM `users`
            WHERE 1=1
            AND user_name = ? COLLATE NOCASE
            """;
        List<Map<String, Object>> rows = query(sql, userName);
        return rows.isEmpty() ? null : UserEntity.fromPrimitives(rows.getFirst());
    }

    /**
     * Fuera de UserEntity a proposito: el hash solo lo necesita el login y nunca debe acabar en una respuesta.
     * "" = cuenta sin password (creada antes de 20261025101838-add-password-hash-to-users.sql).
     */
    public String getPasswordHashByUserId(int userId) {
        String sql = """
            -- getPasswordHashByUserId
            SELECT
                password_hash
            FROM `users`
            WHERE 1=1
            AND id = %d
            """.formatted(userId);
        List<Map<String, Object>> rows = query(sql);
        return rows.isEmpty() ? "" : getTrimmedString(rows.getFirst().get("password_hash"));
    }

    /**
     * Unicidad del user_name en el alta (sin distinguir mayusculas, como el indice).
     */
    public boolean doesUserNameExist(String userName) {
        String sql = """
            -- doesUserNameExist
            SELECT
                COUNT(*) AS n_rows
            FROM `users`
            WHERE 1=1
            AND user_name = ? COLLATE NOCASE
            """;
        return hasRows(query(sql, userName));
    }

    /**
     * Unicidad del user_name en la modificacion: el del propio usuario no cuenta como repetido.
     */
    public boolean doesUserNameExistInOtherUser(UserEntity userEntity) {
        String sql = """
            -- doesUserNameExistInOtherUser
            SELECT
                COUNT(*) AS n_rows
            FROM `users`
            WHERE 1=1
            AND user_name = ? COLLATE NOCASE
            AND id != %d
            """.formatted(userEntity.getId());
        return hasRows(query(sql, userEntity.getUserName()));
    }

    /**
     * Unicidad del email en el alta (sin distinguir mayusculas: la columna es COLLATE NOCASE).
     */
    public boolean doesUserEmailExist(String userEmail) {
        String sql = """
            -- doesUserEmailExist
            SELECT
                COUNT(*) AS n_rows
            FROM `users`
            WHERE 1=1
            AND user_email = ?
            """;
        return hasRows(query(sql, userEmail));
    }

    /**
     * Unicidad del email en la modificacion: el email del propio usuario no cuenta como repetido.
     */
    public boolean doesUserEmailExistInOtherUser(UserEntity userEntity) {
        String sql = """
            -- doesUserEmailExistInOtherUser
            SELECT
                COUNT(*) AS n_rows
            FROM `users`
            WHERE 1=1
            AND user_email = ?
            AND id != %d
            """.formatted(userEntity.getId());
        return hasRows(query(sql, userEntity.getUserEmail()));
    }

    private boolean hasRows(List<Map<String, Object>> rows) {
        return !rows.isEmpty() && getInt(rows.getFirst().get("n_rows")) > 0;
    }
}
