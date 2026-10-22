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
        string $now
    ): int
    {
        $sql = "
        -- insertUser
        INSERT INTO users (user_uuid, user_name, user_email, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?)
        ";
        $this->execute($sql, [$userUuid, $userName, $userEmail, $now, $now]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateUserByUserUuid(
        string $userUuid,
        string $userName,
        string $userEmail,
        string $now
    ): int
    {
        $sql = "
        -- updateUserByUserUuid
        UPDATE users
        SET user_name = ?, user_email = ?, updated_at = ?
        WHERE user_uuid = ?
        ";
        return $this->execute($sql, [$userName, $userEmail, $now, $userUuid]);
    }

    /**
     * Borra tambien sus tasks (FK ON DELETE CASCADE).
     */
    public function deleteUserByUserUuid(string $userUuid): int
    {
        $sql = "
        -- deleteUserByUserUuid
        DELETE FROM users
        WHERE user_uuid = ?
        ";
        return $this->execute($sql, [$userUuid]);
    }
}
