<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Repositories;

use App\Modules\Shared\Infrastructure\Repositories\AbstractSqliteRepository;

final class UsersWriterSqliteRepository extends AbstractSqliteRepository
{
    public static function getInstance(): self
    {
        return new self();
    }

    public function insertUser(
        string $userUuid,
        string $userName,
        string $userEmail,
        string $passwordHash,
        string $apiTokenHash,
        string $now
    ): int
    {
        $sql = "
        -- insertUser
        INSERT INTO `users`
            (user_uuid, user_name, user_email, password_hash, api_token_hash, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?)
        ";
        $this->execute($sql, [$userUuid, $userName, $userEmail, $passwordHash, $apiTokenHash, $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateUserById(
        int $userId,
        string $userName,
        string $userEmail,
        string $now
    ): int
    {
        $sql = "
        -- updateUserById
        UPDATE `users`
        SET user_name = ?, user_email = ?, updated_at = ?
        WHERE 1=1
        AND id = {$userId}
        ";
        return $this->execute($sql, [$userName, $userEmail, $now]);
    }

    /**
     * El token anterior deja de valer en el acto: solo existe el hash nuevo.
     */
    public function updateApiTokenHashByUserId(int $userId, string $apiTokenHash, string $now): int
    {
        $sql = "
        -- updateApiTokenHashByUserId
        UPDATE `users`
        SET api_token_hash = ?, updated_at = ?
        WHERE 1=1
        AND id = {$userId}
        ";
        return $this->execute($sql, [$apiTokenHash, $now]);
    }

    /**
     * Rehash en el login cuando cambia el algoritmo o el coste de password_hash().
     */
    public function updatePasswordHashByUserId(int $userId, string $passwordHash, string $now): int
    {
        $sql = "
        -- updatePasswordHashByUserId
        UPDATE `users`
        SET password_hash = ?, updated_at = ?
        WHERE 1=1
        AND id = {$userId}
        ";
        return $this->execute($sql, [$passwordHash, $now]);
    }

    /**
     * Borra tambien sus tasks (FK ON DELETE CASCADE).
     */
    public function deleteUserById(int $userId): int
    {
        $sql = "
        -- deleteUserById
        DELETE FROM `users`
        WHERE 1=1
        AND id = {$userId}
        ";
        return $this->execute($sql);
    }
}
