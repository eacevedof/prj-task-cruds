from Modules.Shared.Application.Primitives import getTrimmedString
from Modules.Shared.Infrastructure.Repositories.AbstractSqliteRepository import AbstractSqliteRepository

from Modules.Users.Domain.Entities.UserEntity import UserEntity


class UsersReaderSqliteRepository(AbstractSqliteRepository):

    @staticmethod
    def getInstance() -> "UsersReaderSqliteRepository":
        return UsersReaderSqliteRepository()

    def getUserById(self, userId: int) -> UserEntity | None:
        sql = f"""
        -- getUserById
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM `users`
        WHERE 1=1
        AND id = {int(userId)}
        """
        rows = self.query(sql)
        return UserEntity.fromPrimitives(rows[0]) if rows else None

    def getUserByUserUuid(self, userUuid: str) -> UserEntity | None:
        sql = """
        -- getUserByUserUuid
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM `users`
        WHERE 1=1
        AND user_uuid = ?
        """
        rows = self.query(sql, [userUuid])
        return UserEntity.fromPrimitives(rows[0]) if rows else None

    def getUserByApiTokenHash(self, apiTokenHash: str) -> UserEntity | None:
        """
        Autenticacion: el token llega en claro y aqui solo se compara su hash (api_token_hash es UNIQUE).
        """
        sql = """
        -- getUserByApiTokenHash
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM `users`
        WHERE 1=1
        AND api_token_hash = ?
        """
        rows = self.query(sql, [apiTokenHash])
        return UserEntity.fromPrimitives(rows[0]) if rows else None

    def getUserByUserName(self, userName: str) -> UserEntity | None:
        """
        Login: user_name es unico sin distinguir mayusculas (indice uk_users_user_name COLLATE NOCASE).
        """
        sql = """
        -- getUserByUserName
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM `users`
        WHERE 1=1
        AND user_name = ? COLLATE NOCASE
        """
        rows = self.query(sql, [userName])
        return UserEntity.fromPrimitives(rows[0]) if rows else None

    def getPasswordHashByUserId(self, userId: int) -> str:
        """
        Fuera de UserEntity a proposito: el hash solo lo necesita el login y nunca debe acabar en una respuesta.
        "" = cuenta sin password (creada antes de 20261025101838-add-password-hash-to-users.sql).
        """
        sql = f"""
        -- getPasswordHashByUserId
        SELECT
            password_hash
        FROM `users`
        WHERE 1=1
        AND id = {int(userId)}
        """
        rows = self.query(sql)
        return getTrimmedString(rows[0].get("password_hash") if rows else None)

    def doesUserNameExist(self, userName: str) -> bool:
        """
        Unicidad del user_name en el alta (sin distinguir mayusculas, como el indice).
        """
        sql = """
        -- doesUserNameExist
        SELECT
            COUNT(*) AS n_rows
        FROM `users`
        WHERE 1=1
        AND user_name = ? COLLATE NOCASE
        """
        return self._hasRows(self.query(sql, [userName]))

    def doesUserNameExistInOtherUser(self, userEntity: UserEntity) -> bool:
        """
        Unicidad del user_name en la modificacion: el del propio usuario no cuenta como repetido.
        """
        sql = f"""
        -- doesUserNameExistInOtherUser
        SELECT
            COUNT(*) AS n_rows
        FROM `users`
        WHERE 1=1
        AND user_name = ? COLLATE NOCASE
        AND id != {int(userEntity.getId() or 0)}
        """
        return self._hasRows(self.query(sql, [userEntity.getUserName()]))

    def doesUserEmailExist(self, userEmail: str) -> bool:
        """
        Unicidad del email en el alta (sin distinguir mayusculas: la columna es COLLATE NOCASE).
        """
        sql = """
        -- doesUserEmailExist
        SELECT
            COUNT(*) AS n_rows
        FROM `users`
        WHERE 1=1
        AND user_email = ?
        """
        return self._hasRows(self.query(sql, [userEmail]))

    def doesUserEmailExistInOtherUser(self, userEntity: UserEntity) -> bool:
        """
        Unicidad del email en la modificacion: el email del propio usuario no cuenta como repetido.
        """
        sql = f"""
        -- doesUserEmailExistInOtherUser
        SELECT
            COUNT(*) AS n_rows
        FROM `users`
        WHERE 1=1
        AND user_email = ?
        AND id != {int(userEntity.getId() or 0)}
        """
        return self._hasRows(self.query(sql, [userEntity.getUserEmail()]))

    def _hasRows(self, rows: list[dict]) -> bool:
        return bool(rows[0].get("n_rows") or 0) if rows else False
