<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\DeleteUser;

final readonly class DeleteUserDto
{
    public function __construct(
        private string $userUuid,
    ) {
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            trim((string) ($primitives["userUuid"] ?? "")),
        );
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
    }
}
