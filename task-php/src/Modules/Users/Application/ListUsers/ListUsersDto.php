<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\ListUsers;

final readonly class ListUsersDto
{
    public static function fromPrimitives(array $primitives = []): self
    {
        return new self();
    }
}
