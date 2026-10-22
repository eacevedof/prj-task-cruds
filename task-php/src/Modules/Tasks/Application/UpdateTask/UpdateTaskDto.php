<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\UpdateTask;

final readonly class UpdateTaskDto
{
    public function __construct(
        private string $taskUuid,
        private string $taskTitle,
        private string $taskStatus,
        private string $taskType,
        private string $targetDate,
        private string $taskDescription,
    ) {
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            trim((string) ($primitives["taskUuid"] ?? "")),
            trim((string) ($primitives["taskTitle"] ?? "")),
            trim((string) ($primitives["taskStatus"] ?? "")),
            trim((string) ($primitives["taskType"] ?? "")),
            trim((string) ($primitives["targetDate"] ?? "")),
            trim((string) ($primitives["taskDescription"] ?? "")),
        );
    }

    public function getTaskUuid(): string
    {
        return $this->taskUuid;
    }
    public function getTaskTitle(): string
    {
        return $this->taskTitle;
    }
    public function getTaskStatus(): string
    {
        return $this->taskStatus;
    }
    public function getTaskType(): string
    {
        return $this->taskType;
    }
    public function getTargetDate(): string
    {
        return $this->targetDate;
    }
    public function getTaskDescription(): string
    {
        return $this->taskDescription;
    }
}
