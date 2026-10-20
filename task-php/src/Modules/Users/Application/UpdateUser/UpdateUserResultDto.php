<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\UpdateUser;

final readonly class UpdateUserResultDto
{
    /**
     * el usuario modificado (UserEntity::toPrimitives)
     */
    private array $user;

    public function __construct(array $primitives)
    {
        $this->user = $primitives["user"] ?? [];
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    public function getUser(): array
    {
        return $this->user;
    }

    public function toArray(): array
    {
        return $this->user;
    }
}
