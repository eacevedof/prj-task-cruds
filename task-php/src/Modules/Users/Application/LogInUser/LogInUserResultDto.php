<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\LogInUser;

use App\Modules\Users\Domain\Enums\UsersKeyEnum;

final readonly class LogInUserResultDto
{
    /**
     * el usuario (UserEntity::toPrimitives) + su api_token nuevo en claro, solo aqui
     */
    private array $user;
    private string $apiToken;

    public function __construct(array $primitives)
    {
        $this->user = $primitives["user"] ?? [];
        $this->apiToken = trim((string) ($primitives["apiToken"] ?? ""));
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    public function getUser(): array
    {
        return $this->user;
    }

    public function getApiToken(): string
    {
        return $this->apiToken;
    }

    public function toArray(): array
    {
        return array_merge(
            $this->user,
            [UsersKeyEnum::API_TOKEN => $this->apiToken]
        );
    }
}
