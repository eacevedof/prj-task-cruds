<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\DeleteUser;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;

final readonly class DeleteUserResultDto
{
    private string $userUuid;
    private int $affected;

    public function __construct(array $primitives)
    {
        $this->userUuid = trim((string) ($primitives["userUuid"] ?? ""));
        $this->affected = (int) ($primitives["affected"] ?? 0);
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    public function getAffected(): int
    {
        return $this->affected;
    }

    public function toArray(): array
    {
        return [
            UsersKeyEnum::USER_UUID => $this->userUuid,
            UsersKeyEnum::AFFECTED => $this->affected,
        ];
    }
}
