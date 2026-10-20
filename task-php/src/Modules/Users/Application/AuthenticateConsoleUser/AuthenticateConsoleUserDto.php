<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\AuthenticateConsoleUser;

use App\Modules\Shared\Application\AbstractInputDto;

final readonly class AuthenticateConsoleUserDto extends AbstractInputDto
{
    private string $userUuid;

    public function __construct(array $primitives)
    {
        parent::__construct($primitives);

        $this->userUuid = trim((string) ($primitives["userUuid"] ?? ""));
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
    }
}
