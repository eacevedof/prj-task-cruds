<?php
declare(strict_types=1);

namespace App\Modules\Users\Domain\Entities;

final readonly class UserEntity
{
    public function __construct(
        private int    $id,
        private string $userUuid,
        private string $userName,
        private string $userEmail,
        private string $createdAt,
        private string $updatedAt,
    ) {
    }

    /**
     * Desde una fila de la tabla users.
     */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            (int) ($primitives["id"] ?? 0),
            (string) ($primitives["user_uuid"] ?? ""),
            (string) ($primitives["user_name"] ?? ""),
            (string) ($primitives["user_email"] ?? ""),
            (string) ($primitives["created_at"] ?? ""),
            (string) ($primitives["updated_at"] ?? ""),
        );
    }

    /**
     * Representacion publica (API): el id interno no sale, el identificador es user_uuid.
     */
    public function toPrimitives(): array
    {
        return [
            "user_uuid" => $this->userUuid,
            "user_name" => $this->userName,
            "user_email" => $this->userEmail,
            "created_at" => $this->createdAt,
            "updated_at" => $this->updatedAt,
        ];
    }

    public function getId(): int
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
