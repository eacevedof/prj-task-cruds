<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Domain\Entities;

final readonly class TaskEntity
{
    public function __construct(
        private int     $id,
        private int     $userId,
        private string  $userUuid,
        private string  $taskUuid,
        private string  $taskTitle,
        private string  $taskStatus,
        private string  $taskType,
        private ?string $targetDate,
        private ?string $taskDescription,
        private string  $createdAt,
        private string  $updatedAt,
    ) {
    }

    /**
     * Desde una fila de tasks JOIN users (user_uuid viene del usuario propietario).
     */
    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            (int) ($primitives["id"] ?? 0),
            (int) ($primitives["user_id"] ?? 0),
            (string) ($primitives["user_uuid"] ?? ""),
            (string) ($primitives["task_uuid"] ?? ""),
            (string) ($primitives["task_title"] ?? ""),
            (string) ($primitives["task_status"] ?? ""),
            (string) ($primitives["task_type"] ?? ""),
            $primitives["target_date"] ?? null,
            $primitives["task_description"] ?? null,
            (string) ($primitives["created_at"] ?? ""),
            (string) ($primitives["updated_at"] ?? ""),
        );
    }

    /**
     * Representacion publica (API): sin ids internos; el propietario va por user_uuid.
     */
    public function toPrimitives(): array
    {
        return [
            "task_uuid" => $this->taskUuid,
            "user_uuid" => $this->userUuid,
            "task_title" => $this->taskTitle,
            "task_status" => $this->taskStatus,
            "task_type" => $this->taskType,
            "target_date" => $this->targetDate,
            "task_description" => $this->taskDescription,
            "created_at" => $this->createdAt,
            "updated_at" => $this->updatedAt,
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }
    public function getUserId(): int
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
    public function getTargetDate(): ?string
    {
        return $this->targetDate;
    }
    public function getTaskDescription(): ?string
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
