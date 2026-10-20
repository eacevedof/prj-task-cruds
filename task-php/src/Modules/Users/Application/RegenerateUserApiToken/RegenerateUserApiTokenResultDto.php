<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\RegenerateUserApiToken;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;

final readonly class RegenerateUserApiTokenResultDto
{
    public function __construct(
        private string $userUuid,
        private string $apiToken,
    ) {
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            trim((string) ($primitives["userUuid"] ?? "")),
            trim((string) ($primitives["apiToken"] ?? "")),
        );
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    public function getApiToken(): string
    {
        return $this->apiToken;
    }

    public function toArray(): array
    {
        return [
            UsersKeyEnum::USER_UUID => $this->userUuid,
            UsersKeyEnum::API_TOKEN => $this->apiToken,
        ];
    }
}
