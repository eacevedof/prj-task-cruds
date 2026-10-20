<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Repositories;

use App\Modules\Shared\Infrastructure\Repositories\AbstractSqliteRepository;

use App\Modules\Tasks\Domain\Entities\TaskEntity;

final class TasksWriterSqliteRepository extends AbstractSqliteRepository
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * target_date / task_description vacios se guardan como NULL. En el alta updated_at = created_at.
     */
    public function insertTask(TaskEntity $taskEntity): int
    {
        $sql = "
        -- insertTask
        INSERT INTO `tasks`
        (user_id, task_uuid, task_title, task_status, task_type, target_date, task_description, created_at, updated_at)
        VALUES
        ({$taskEntity->getUserId()}, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $this->execute($sql, [
            $taskEntity->getTaskUuid(),
            $taskEntity->getTaskTitle(),
            $taskEntity->getTaskStatus(),
            $taskEntity->getTaskType(),
            $taskEntity->getTargetDate() ?: null,
            $taskEntity->getTaskDescription() ?: null,
            $taskEntity->getCreatedAt(),
            $taskEntity->getCreatedAt(),
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * El propietario y created_at no cambian: solo los campos editables y updated_at.
     */
    public function updateTaskById(TaskEntity $taskEntity): int
    {
        $sql = "
        -- updateTaskById
        UPDATE `tasks`
        SET 
            task_title = ?, 
            task_status = ?, 
            task_type = ?, 
            target_date = ?, 
            task_description = ?, 
            updated_at = ?
        WHERE 1=1
        AND id = ?
        ";
        return $this->execute($sql, [
            $taskEntity->getTaskTitle(),
            $taskEntity->getTaskStatus(),
            $taskEntity->getTaskType(),
            $taskEntity->getTargetDate() ?: null,
            $taskEntity->getTaskDescription() ?: null,
            $taskEntity->getUpdatedAt(),
            $taskEntity->getId(),
        ]);
    }

    public function deleteTaskById(int $taskId): int
    {
        $sql = "
        -- deleteTaskById
        DELETE FROM `tasks`
        WHERE 1=1
        AND id = {$taskId}
        ";
        return $this->execute($sql);
    }
}
