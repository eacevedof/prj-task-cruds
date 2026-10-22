<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Repositories;

use App\Modules\Shared\Infrastructure\Repositories\AbstractSqliteRepository;

final class TasksWriterSqliteRepository extends AbstractSqliteRepository
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * target_date / task_description vacios se guardan como NULL.
     */
    public function insertTask(
        int $userId,
        string $taskUuid,
        string $taskTitle,
        string $taskStatus,
        string $taskType,
        string $targetDate,
        string $taskDescription,
        string $now
    ): int
    {
        $sql = "
        -- insertTask
        INSERT INTO tasks (user_id, task_uuid, task_title, task_status, task_type, target_date, task_description, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $this->execute($sql, [
            $userId, $taskUuid, $taskTitle, $taskStatus, $taskType,
            $targetDate ?: null, $taskDescription ?: null, $now, $now,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateTaskByTaskUuid(
        string $taskUuid,
        string $taskTitle,
        string $taskStatus,
        string $taskType,
        string $targetDate,
        string $taskDescription,
        string $now
    ): int
    {
        $sql = "
        -- updateTaskByTaskUuid
        UPDATE tasks
        SET task_title = ?, task_status = ?, task_type = ?, target_date = ?, task_description = ?, updated_at = ?
        WHERE task_uuid = ?
        ";
        return $this->execute($sql, [
            $taskTitle, $taskStatus, $taskType, $targetDate ?: null, $taskDescription ?: null, $now, $taskUuid,
        ]);
    }

    public function deleteTaskByTaskUuid(string $taskUuid): int
    {
        $sql = "
        -- deleteTaskByTaskUuid
        DELETE FROM tasks
        WHERE task_uuid = ?
        ";
        return $this->execute($sql, [$taskUuid]);
    }
}
