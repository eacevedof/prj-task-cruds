<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\GetUser;

use App\Modules\Shared\Application\AbstractInputDto;

final readonly class GetUserDto extends AbstractInputDto
{
    private int $authUserId;
    private string $authUserUuid;
    private string $userUuid;

    public function __construct(array $primitives)
    {
        parent::__construct($primitives);

        $this->authUserId = (int) ($primitives["authUserId"] ?? 0);
        $this->authUserUuid = trim((string) ($primitives["authUserUuid"] ?? ""));
        $this->userUuid = trim((string) ($primitives["userUuid"] ?? ""));
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

    public function getUserUuid(): string
    {
        return $this->userUuid;
    }
}
