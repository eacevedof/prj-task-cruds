<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\ListUsers;

use App\Modules\Users\Domain\Entities\UserEntity;

final readonly class ListUsersResultDto
{
    /** @param UserEntity[] $userEntities */
    public function __construct(
        private array $userEntities,
    ) {
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives["userEntities"] ?? []);
    }

    /** @return UserEntity[] */
    public function getUserEntities(): array
    {
        return $this->userEntities;
    }

    public function toArray(): array
    {
        return array_map(
            fn(UserEntity $userEntity) => $userEntity->toPrimitives(),
            $this->userEntities
        );
    }
}
