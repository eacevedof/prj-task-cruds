<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\ListUsers;

use App\Modules\Shared\Application\AbstractInputDto;

final readonly class ListUsersDto extends AbstractInputDto
{
    private int $authUserId;
    private string $authUserUuid;

    public function __construct(array $primitives)
    {
        parent::__construct($primitives);

        $this->authUserId = (int) ($primitives["authUserId"] ?? 0);
        $this->authUserUuid = trim((string) ($primitives["authUserUuid"] ?? ""));
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    public function getAuthUserId(): int
    {
        return $this->authUserId;
    }

    public function getAuthUserUuid(): string
    {
        return $this->authUserUuid;
    }
}
