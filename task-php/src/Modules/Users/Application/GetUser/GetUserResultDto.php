<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\GetUser;

final readonly class GetUserResultDto
{
    /**
     * el usuario (UserEntity::toPrimitives)
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
