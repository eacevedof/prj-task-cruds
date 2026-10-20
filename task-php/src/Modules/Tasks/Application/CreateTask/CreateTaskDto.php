<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\CreateTask;

use App\Modules\Shared\Application\AbstractInputDto;

final readonly class CreateTaskDto extends AbstractInputDto
{
    private int $authUserId;
    private string $authUserUuid;
    private string $taskTitle;
    private string $taskStatus;
    private string $taskType;
    private string $targetDate;
    private string $taskDescription;

    public function __construct(array $primitives)
    {
        parent::__construct($primitives);

        $this->authUserId = (int) ($primitives["authUserId"] ?? 0);
        $this->authUserUuid = trim((string) ($primitives["authUserUuid"] ?? ""));

        $this->taskTitle = trim((string) ($primitives["taskTitle"] ?? ""));
        $this->taskStatus = trim((string) ($primitives["taskStatus"] ?? ""));
        $this->taskType = trim((string) ($primitives["taskType"] ?? ""));
        $this->targetDate = trim((string) ($primitives["targetDate"] ?? ""));
        $this->taskDescription = trim((string) ($primitives["taskDescription"] ?? ""));
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
