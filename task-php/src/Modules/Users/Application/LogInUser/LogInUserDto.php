<?php
declare(strict_types=1);

namespace App\Modules\Users\Application\LogInUser;

use App\Modules\Shared\Application\AbstractInputDto;

final readonly class LogInUserDto extends AbstractInputDto
{
    private string $userName;
    private string $password;

    public function __construct(array $primitives)
    {
        parent::__construct($primitives);

        $this->userName = trim((string) ($primitives["userName"] ?? ""));
        $this->password = trim((string) ($primitives["password"] ?? ""));
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
