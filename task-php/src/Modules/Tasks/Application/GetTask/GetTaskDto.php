<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\GetTask;

use App\Modules\Shared\Application\AbstractInputDto;

final readonly class GetTaskDto extends AbstractInputDto
{
    private int $authUserId;
    private string $authUserUuid;
    private string $taskUuid;

    public function __construct(array $primitives)
    {
        parent::__construct($primitives);

        $this->authUserId = (int) ($primitives["authUserId"] ?? 0);
        $this->authUserUuid = trim((string) ($primitives["authUserUuid"] ?? ""));
        $this->taskUuid = trim((string) ($primitives["taskUuid"] ?? ""));
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    public function getAuthUserId(): int
    {
        return $this->authUserId;
    }

    public function getAuthUserUuid(): string
    {
        return $this->authUserUuid;
    }

    public function getTaskUuid(): string
    {
        return $this->taskUuid;
    }
}
