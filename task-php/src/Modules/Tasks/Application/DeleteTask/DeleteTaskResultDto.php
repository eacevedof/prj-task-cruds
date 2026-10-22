<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\DeleteTask;

final readonly class DeleteTaskResultDto
{
    public function __construct(
        private string $taskUuid,
        private int $affected,
    ) {
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            (string) ($primitives["taskUuid"] ?? ""),
            (int) ($primitives["affected"] ?? 0),
        );
    }

    public function getTaskUuid(): string
    {
        return $this->taskUuid;
    }

    public function toArray(): array
    {
        return [
            "task_uuid" => $this->taskUuid,
            "affected" => $this->affected,
        ];
    }
}
