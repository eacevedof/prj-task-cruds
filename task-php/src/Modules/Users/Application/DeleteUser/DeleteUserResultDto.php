<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\DeleteUser;

final readonly class DeleteUserResultDto
{
    public function __construct(
        private string $userUuid,
        private int $affected,
    ) {
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            (string) ($primitives["userUuid"] ?? ""),
            (int) ($primitives["affected"] ?? 0),
        );
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    public function toArray(): array
    {
        return [
            "user_uuid" => $this->userUuid,
            "affected" => $this->affected,
        ];
    }
}
