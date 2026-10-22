<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\UpdateUser;

final readonly class UpdateUserDto
{
    public function __construct(
        private string $userUuid,
        private string $userName,
        private string $userEmail,
    ) {
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            trim((string) ($primitives["userUuid"] ?? "")),
            trim((string) ($primitives["userName"] ?? "")),
            trim((string) ($primitives["userEmail"] ?? "")),
        );
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
}
