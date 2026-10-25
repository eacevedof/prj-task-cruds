<?php
declare(strict_types=1);

namespace App\Modules\Users\Domain\Entities;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;

final readonly class UserEntity
{
    public const string USER_UUID_PREFIX = "user-";

    private ?int $id;
    private string $userUuid;
    private string $userName;
    private string $userEmail;
    private string $passwordHash;
    private string $apiTokenHash;
    private string $createdAt;
    private string $updatedAt;

    /**
     * Desde una fila de la tabla users (columnas en snake_case: contrato con la BD). Las lecturas no traen
     * password_hash ni api_token_hash: solo los informan las escrituras.
     */
    public function __construct(array $primitives)
    {
        $this->id = isset($primitives[UsersKeyEnum::ID]) ? (int) $primitives[UsersKeyEnum::ID] : null;
        $this->userUuid = trim((string) ($primitives[UsersKeyEnum::USER_UUID] ?? ""));
        $this->userName = trim((string) ($primitives[UsersKeyEnum::USER_NAME] ?? ""));
        $this->userEmail = trim((string) ($primitives[UsersKeyEnum::USER_EMAIL] ?? ""));
        $this->passwordHash = trim((string) ($primitives[UsersKeyEnum::PASSWORD_HASH] ?? ""));
        $this->apiTokenHash = trim((string) ($primitives[UsersKeyEnum::API_TOKEN_HASH] ?? ""));
        $this->createdAt = trim((string) ($primitives[UsersKeyEnum::CREATED_AT] ?? ""));
        $this->updatedAt = trim((string) ($primitives[UsersKeyEnum::UPDATED_AT] ?? ""));
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    /**
     * Representacion publica (API): el id interno no sale, el identificador es user_uuid. Los hashes nunca.
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

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getApiTokenHash(): string
    {
        return $this->apiTokenHash;
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
