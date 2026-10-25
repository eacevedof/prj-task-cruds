<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Repositories;

use App\Modules\Shared\Infrastructure\Repositories\AbstractSqliteRepository;

use App\Modules\Users\Domain\Entities\UserEntity;

final class UsersWriterSqliteRepository extends AbstractSqliteRepository
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * En el alta updated_at = created_at.
     */
    public function insertUser(UserEntity $userEntity): int
    {
        $sql = "
        -- insertUser
        INSERT INTO `users`
            (user_uuid, user_name, user_email, password_hash, api_token_hash, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?)
        ";
        $this->execute($sql, [
            $userEntity->getUserUuid(),
            $userEntity->getUserName(),
            $userEntity->getUserEmail(),
            $userEntity->getPasswordHash(),
            $userEntity->getApiTokenHash(),
            $userEntity->getCreatedAt(),
            $userEntity->getCreatedAt(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateUserById(UserEntity $userEntity): int
    {
        $sql = "
        -- updateUserById
        UPDATE `users`
        SET user_name = ?, user_email = ?, updated_at = ?
        WHERE 1=1
        AND id = {$userEntity->getId()}
        ";
        return $this->execute($sql, [
            $userEntity->getUserName(),
            $userEntity->getUserEmail(),
            $userEntity->getUpdatedAt(),
        ]);
    }

    /**
     * El token anterior deja de valer en el acto: solo existe el hash nuevo.
     */
    public function updateApiTokenHashById(UserEntity $userEntity): int
    {
        $sql = "
        -- updateApiTokenHashById
        UPDATE `users`
        SET api_token_hash = ?, updated_at = ?
        WHERE 1=1
        AND id = {$userEntity->getId()}
        ";
        return $this->execute($sql, [$userEntity->getApiTokenHash(), $userEntity->getUpdatedAt()]);
    }

    /**
     * Rehash en el login cuando cambia el algoritmo o el coste de password_hash().
     */
    public function updatePasswordHashById(UserEntity $userEntity): int
    {
        $sql = "
        -- updatePasswordHashById
        UPDATE `users`
        SET password_hash = ?, updated_at = ?
        WHERE 1=1
        AND id = {$userEntity->getId()}
        ";
        return $this->execute($sql, [$userEntity->getPasswordHash(), $userEntity->getUpdatedAt()]);
    }

    /**
     * Borra tambien sus tasks (FK ON DELETE CASCADE).
     */
    public function deleteUserById(UserEntity $userEntity): int
    {
        $sql = "
        -- deleteUserById
        DELETE FROM `users`
        WHERE 1=1
        AND id = {$userEntity->getId()}
        ";
        return $this->execute($sql);
    }
}
