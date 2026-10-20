<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\AuthenticateUser;

/**
 * userId es el que usan todas las queries (mas ligero que el uuid); userUuid solo para comparar con el
 * user_uuid que llega de fuera (ruta, argumento GraphQL, opcion de consola).
 */
final readonly class AuthenticateUserResultDto
{
    public function __construct(
        private int $userId,
        private string $userUuid,
    ) {
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            (int) ($primitives["userId"] ?? 0),
            trim((string) ($primitives["userUuid"] ?? "")),
        );
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    public function toAuthPrimitives(): array
    {
        return [
            "authUserId" => $this->userId,
            "authUserUuid" => $this->userUuid,
        ];
    }
}
