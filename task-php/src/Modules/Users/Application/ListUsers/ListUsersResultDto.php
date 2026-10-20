<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\ListUsers;

final readonly class ListUsersResultDto
{
    /**
     * lista de UserEntity::toPrimitives
     */
    private array $users;

    public function __construct(array $primitives)
    {
        $this->users = $primitives["users"] ?? [];
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    public function getUsers(): array
    {
        return $this->users;
    }

    public function toArray(): array
    {
        return $this->users;
    }
}
