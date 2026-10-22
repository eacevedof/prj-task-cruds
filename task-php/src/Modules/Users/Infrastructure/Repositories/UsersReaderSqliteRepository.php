<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Repositories;

use App\Modules\Shared\Infrastructure\Repositories\AbstractSqliteRepository;

use App\Modules\Users\Domain\Entities\UserEntity;

final class UsersReaderSqliteRepository extends AbstractSqliteRepository
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @return UserEntity[]
     */
    public function getUsers(): array
    {
        $sql = "
        -- getUsers
        SELECT id, user_uuid, user_name, user_email, created_at, updated_at
        FROM users
        ORDER BY id
        ";
        return array_map(
            fn(array $row) => UserEntity::fromPrimitives($row),
            $this->query($sql)
        );
    }

    public function getUserByUserUuid(string $userUuid): ?UserEntity
    {
        $sql = "
        -- getUserByUserUuid
        SELECT id, user_uuid, user_name, user_email, created_at, updated_at
        FROM users
        WHERE user_uuid = ?
        ";
        $rows = $this->query($sql, [$userUuid]);
        return $rows ? UserEntity::fromPrimitives($rows[0]) : null;
    }

    /**
     * Para la unicidad del email (sin distinguir mayusculas: la columna es COLLATE NOCASE).
     * $exceptUserUuid excluye al propio usuario en la modificacion.
     */
    public function doesUserEmailExist(string $userEmail, string $exceptUserUuid = ""): bool
    {
        $sql = "
        -- doesUserEmailExist
        SELECT COUNT(*) AS n_rows
        FROM users
        WHERE user_email = ?
        AND user_uuid != ?
        ";
        $rows = $this->query($sql, [$userEmail, $exceptUserUuid]);
        return (bool) ($rows[0]["n_rows"] ?? 0);
    }
}
