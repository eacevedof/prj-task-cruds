<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\AuthenticateUser;

use App\Modules\Shared\Application\AbstractInputDto;

final readonly class AuthenticateUserDto extends AbstractInputDto
{
    private string $apiToken;

    public function __construct(array $primitives)
    {
        parent::__construct($primitives);

        $this->apiToken = trim((string) ($primitives["apiToken"] ?? ""));
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    public function getApiToken(): string
    {
        return $this->apiToken;
    }
}
