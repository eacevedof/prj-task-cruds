<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\CreateUser;

use App\Modules\Users\Domain\Entities\UserEntity;

final readonly class CreateUserResultDto
{
    public function __construct(
        private UserEntity $userEntity,
    ) {
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives["userEntity"]);
    }

    public function getUserEntity(): UserEntity
    {
        return $this->userEntity;
    }

    public function toArray(): array
    {
        return $this->userEntity->toPrimitives();
    }
}
