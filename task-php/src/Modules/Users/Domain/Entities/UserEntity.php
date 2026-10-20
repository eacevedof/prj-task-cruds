<?php
declare(strict_types=1);

namespace App\Modules\Users\Domain\Entities;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;

final readonly class UserEntity
{
    private ?int $id;
    private string $userUuid;
    private string $userName;
    private string $userEmail;
    private string $createdAt;
    private string $updatedAt;

    /**
     * Desde una fila de la tabla users (columnas en snake_case: contrato con la BD).
     */
    public function __construct(array $primitives)
    {
        $this->id = isset($primitives["id"]) ? (int) $primitives["id"] : null;
        $this->userUuid = trim((string) ($primitives["user_uuid"] ?? ""));
        $this->userName = trim((string) ($primitives["user_name"] ?? ""));
        $this->userEmail = trim((string) ($primitives["user_email"] ?? ""));
        $this->createdAt = trim((string) ($primitives["created_at"] ?? ""));
        $this->updatedAt = trim((string) ($primitives["updated_at"] ?? ""));
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    /**
     * Representacion publica (API): el id interno no sale, el identificador es user_uuid.
     */
    public function toPrimitives(): array
    {
        return [
            UsersKeyEnum::USER_UUID => $this->userUuid,
            UsersKeyEnum::USER_NAME => $this->userName,
            UsersKeyEnum::USER_EMAIL => $this->userEmail,
            UsersKeyEnum::CREATED_AT => $this->createdAt,
            UsersKeyEnum::UPDATED_AT => $this->updatedAt,
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }
}
