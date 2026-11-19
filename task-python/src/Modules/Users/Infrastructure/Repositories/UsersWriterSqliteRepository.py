from Modules.Shared.Infrastructure.Repositories.AbstractSqliteRepository import AbstractSqliteRepository

from Modules.Users.Domain.Entities.UserEntity import UserEntity


class UsersWriterSqliteRepository(AbstractSqliteRepository):

    @staticmethod
    def getInstance() -> "UsersWriterSqliteRepository":
        return UsersWriterSqliteRepository()

    def insertUser(self, userEntity: UserEntity) -> int:
        """
        En el alta updated_at = created_at.
        """
        sql = """
        -- insertUser
        INSERT INTO `users`
            (user_uuid, user_name, user_email, password_hash, api_token_hash, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?)
        """
        self.execute(sql, [
            userEntity.getUserUuid(),
            userEntity.getUserName(),
            userEntity.getUserEmail(),
            userEntity.getPasswordHash(),
            userEntity.getApiTokenHash(),
            userEntity.getCreatedAt(),
            userEntity.getCreatedAt(),
        ])
        return self.lastInsertId()

    def updateUserById(self, userEntity: UserEntity) -> int:
        sql = f"""
        -- updateUserById
        UPDATE `users`
        SET user_name = ?, user_email = ?, updated_at = ?
        WHERE 1=1
        AND id = {int(userEntity.getId() or 0)}
        """
        return self.execute(sql, [
            userEntity.getUserName(),
            userEntity.getUserEmail(),
            userEntity.getUpdatedAt(),
        ])

    def updateApiTokenHashById(self, userEntity: UserEntity) -> int:
        """
        El token anterior deja de valer en el acto: solo existe el hash nuevo.
        """
        sql = f"""
        -- updateApiTokenHashById
        UPDATE `users`
        SET api_token_hash = ?, updated_at = ?
        WHERE 1=1
        AND id = {int(userEntity.getId() or 0)}
        """
        return self.execute(sql, [userEntity.getApiTokenHash(), userEntity.getUpdatedAt()])

    def updatePasswordHashById(self, userEntity: UserEntity) -> int:
        """
        Rehash en el login cuando cambia el algoritmo o el coste del hash.
        """
        sql = f"""
        -- updatePasswordHashById
        UPDATE `users`
        SET password_hash = ?, updated_at = ?
        WHERE 1=1
        AND id = {int(userEntity.getId() or 0)}
        """
        return self.execute(sql, [userEntity.getPasswordHash(), userEntity.getUpdatedAt()])

    def deleteUserById(self, userEntity: UserEntity) -> int:
        """
        Borra tambien sus tasks (FK ON DELETE CASCADE).
        """
        sql = f"""
        -- deleteUserById
        DELETE FROM `users`
        WHERE 1=1
        AND id = {int(userEntity.getId() or 0)}
        """
        return self.execute(sql)
