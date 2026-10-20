<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Domain\Entities;

use App\Modules\Tasks\Domain\Enums\TasksKeyEnum;

final readonly class TaskEntity
{
    public const string TASK_UUID_PREFIX = "task-";

    private ?int $id;
    private ?int $userId;
    private string $userUuid;
    private string $taskUuid;
    private string $taskTitle;
    private string $taskStatus;
    private string $taskType;
    private string $targetDate;
    private string $taskDescription;
    private string $createdAt;
    private string $updatedAt;

    /**
     * Desde una fila de tasks JOIN users (user_uuid viene del usuario propietario; columnas en snake_case:
     * contrato con la BD). target_date / task_description NULL en BD -> "" aqui (neutro de string).
     */
    public function __construct(array $primitives)
    {
        $this->id = isset($primitives[TasksKeyEnum::ID]) ? (int) $primitives[TasksKeyEnum::ID] : null;
        $this->userId = isset($primitives[TasksKeyEnum::USER_ID]) ? (int) $primitives[TasksKeyEnum::USER_ID] : null;
        $this->userUuid = trim((string) ($primitives[TasksKeyEnum::USER_UUID] ?? ""));
        $this->taskUuid = trim((string) ($primitives[TasksKeyEnum::TASK_UUID] ?? ""));
        $this->taskTitle = trim((string) ($primitives[TasksKeyEnum::TASK_TITLE] ?? ""));
        $this->taskStatus = trim((string) ($primitives[TasksKeyEnum::TASK_STATUS] ?? ""));
        $this->taskType = trim((string) ($primitives[TasksKeyEnum::TASK_TYPE] ?? ""));
        $this->targetDate = trim((string) ($primitives[TasksKeyEnum::TARGET_DATE] ?? ""));
        $this->taskDescription = trim((string) ($primitives[TasksKeyEnum::TASK_DESCRIPTION] ?? ""));
        $this->createdAt = trim((string) ($primitives[TasksKeyEnum::CREATED_AT] ?? ""));
        $this->updatedAt = trim((string) ($primitives[TasksKeyEnum::UPDATED_AT] ?? ""));
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    /**
     * Representacion publica (API): el identificador para operar es task_uuid; task_id es solo informativo
     * (orden, referencia legible). El propietario va por user_uuid, nunca por su id interno.
     * Los opcionales vacios salen como null (contrato de la API).
     */
    public function toPrimitives(): array
    {
        return [
            TasksKeyEnum::TASK_ID => $this->id,
            TasksKeyEnum::TASK_UUID => $this->taskUuid,
            TasksKeyEnum::USER_UUID => $this->userUuid,
            TasksKeyEnum::TASK_TITLE => $this->taskTitle,
            TasksKeyEnum::TASK_STATUS => $this->taskStatus,
            TasksKeyEnum::TASK_TYPE => $this->taskType,
            TasksKeyEnum::TARGET_DATE => $this->targetDate ?: null,
            TasksKeyEnum::TASK_DESCRIPTION => $this->taskDescription ?: null,
            TasksKeyEnum::CREATED_AT => $this->createdAt,
            TasksKeyEnum::UPDATED_AT => $this->updatedAt,
        ];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
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

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }
}
