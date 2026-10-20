<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\DeleteTask;

use App\Modules\Tasks\Domain\Enums\TasksKeyEnum;

final readonly class DeleteTaskResultDto
{
    private string $taskUuid;
    private int $affected;

    public function __construct(array $primitives)
    {
        $this->taskUuid = trim((string) ($primitives["taskUuid"] ?? ""));
        $this->affected = (int) ($primitives["affected"] ?? 0);
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    public function getTaskUuid(): string
    {
        return $this->taskUuid;
    }

    public function getAffected(): int
    {
        return $this->affected;
    }

    public function toArray(): array
    {
        return [
            TasksKeyEnum::TASK_UUID => $this->taskUuid,
            TasksKeyEnum::AFFECTED => $this->affected,
        ];
    }
}
