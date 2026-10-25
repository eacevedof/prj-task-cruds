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

    public function getUserById(int $userId): ?UserEntity
    {
        $sql = "
        -- getUserById
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM `users`
        WHERE 1=1
        AND id = {$userId}
        ";
        $rows = $this->query($sql);
        return $rows ? UserEntity::fromPrimitives($rows[0]) : null;
    }

    public function getUserByUserUuid(string $userUuid): ?UserEntity
    {
        $sql = "
        -- getUserByUserUuid
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM `users`
        WHERE 1=1
        AND user_uuid = ?
        ";
        $rows = $this->query($sql, [$userUuid]);
        return $rows ? UserEntity::fromPrimitives($rows[0]) : null;
    }

    /**
     * Autenticacion: el token llega en claro y aqui solo se compara su hash (api_token_hash es UNIQUE).
     */
    public function getUserByApiTokenHash(string $apiTokenHash): ?UserEntity
    {
        $sql = "
        -- getUserByApiTokenHash
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM `users`
        WHERE 1=1
        AND api_token_hash = ?
        ";
        $rows = $this->query($sql, [$apiTokenHash]);
        return $rows ? UserEntity::fromPrimitives($rows[0]) : null;
    }

    /**
     * Login: user_name es unico sin distinguir mayusculas (indice uk_users_user_name COLLATE NOCASE).
     */
    public function getUserByUserName(string $userName): ?UserEntity
    {
        $sql = "
        -- getUserByUserName
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM `users`
        WHERE 1=1
        AND user_name = ? COLLATE NOCASE
        ";
        $rows = $this->query($sql, [$userName]);
        return $rows ? UserEntity::fromPrimitives($rows[0]) : null;
    }

    /**
     * Fuera de UserEntity a proposito: el hash solo lo necesita el login y nunca debe acabar en una respuesta.
     * "" = cuenta sin password (creada antes de 20261025101838-add-password-hash-to-users.sql).
     */
    public function getPasswordHashByUserId(int $userId): string
    {
        $sql = "
        -- getPasswordHashByUserId
        SELECT
            password_hash
        FROM `users`
        WHERE 1=1
        AND id = {$userId}
        ";
        $rows = $this->query($sql);
        return (string) ($rows[0]["password_hash"] ?? "");
    }

    /**
     * Unicidad del user_name en el alta (sin distinguir mayusculas, como el indice).
     */
    public function doesUserNameExist(string $userName): bool
    {
        $sql = "
        -- doesUserNameExist
        SELECT
            COUNT(*) AS n_rows
        FROM `users`
        WHERE 1=1
        AND user_name = ? COLLATE NOCASE
        ";
        $rows = $this->query($sql, [$userName]);
        return (bool) ($rows[0]["n_rows"] ?? 0);
    }

    /**
     * Unicidad del user_name en la modificacion: el del propio usuario no cuenta como repetido.
     */
    public function doesUserNameExistInOtherUser(UserEntity $userEntity): bool
    {
        $sql = "
        -- doesUserNameExistInOtherUser
        SELECT
            COUNT(*) AS n_rows
        FROM `users`
        WHERE 1=1
        AND user_name = ? COLLATE NOCASE
        AND id != {$userEntity->getId()}
        ";
        $rows = $this->query($sql, [$userEntity->getUserName()]);
        return (bool) ($rows[0]["n_rows"] ?? 0);
    }

    /**
     * Unicidad del email en el alta (sin distinguir mayusculas: la columna es COLLATE NOCASE).
     */
    public function doesUserEmailExist(string $userEmail): bool
    {
        $sql = "
        -- doesUserEmailExist
        SELECT
            COUNT(*) AS n_rows
        FROM `users`
        WHERE 1=1
        AND user_email = ?
        ";
        $rows = $this->query($sql, [$userEmail]);
        return (bool) ($rows[0]["n_rows"] ?? 0);
    }

    /**
     * Unicidad del email en la modificacion: el email del propio usuario no cuenta como repetido.
     */
    public function doesUserEmailExistInOtherUser(UserEntity $userEntity): bool
    {
        $sql = "
        -- doesUserEmailExistInOtherUser
        SELECT
            COUNT(*) AS n_rows
        FROM `users`
        WHERE 1=1
        AND user_email = ?
        AND id != {$userEntity->getId()}
        ";
        $rows = $this->query($sql, [$userEntity->getUserEmail()]);
        return (bool) ($rows[0]["n_rows"] ?? 0);
    }
}
