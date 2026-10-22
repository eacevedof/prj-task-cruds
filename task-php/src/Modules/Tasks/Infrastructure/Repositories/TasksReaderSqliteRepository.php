<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Repositories;

use App\Modules\Shared\Infrastructure\Repositories\AbstractSqliteRepository;

use App\Modules\Tasks\Domain\Entities\TaskEntity;

final class TasksReaderSqliteRepository extends AbstractSqliteRepository
{
    private const string SELECT_TASKS = "
        SELECT t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
            t.target_date, t.task_description, t.created_at, t.updated_at
        FROM tasks t
        INNER JOIN users u ON u.id = t.user_id
    ";

    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @return TaskEntity[]
     */
    public function getTasks(): array
    {
        $sql = "-- getTasks".self::SELECT_TASKS."ORDER BY t.id";
        return $this->getTaskEntities($this->query($sql));
    }

    /**
     * @return TaskEntity[]
     */
    public function getTasksByUserId(int $userId): array
    {
        $sql = "-- getTasksByUserId".self::SELECT_TASKS."WHERE t.user_id = ? ORDER BY t.id";
        return $this->getTaskEntities($this->query($sql, [$userId]));
    }

    public function getTaskByTaskUuid(string $taskUuid): ?TaskEntity
    {
        $sql = "-- getTaskByTaskUuid".self::SELECT_TASKS."WHERE t.task_uuid = ?";
        $rows = $this->query($sql, [$taskUuid]);
        return $rows ? TaskEntity::fromPrimitives($rows[0]) : null;
    }

    /**
     * Propietario de una task: el modulo Tasks solo necesita el id interno del user (FK), no su entidad.
     */
    public function getUserIdByUserUuid(string $userUuid): int
    {
        $sql = "
        -- getUserIdByUserUuid
        SELECT id
        FROM users
        WHERE user_uuid = ?
        ";
        $rows = $this->query($sql, [$userUuid]);
        return (int) ($rows[0]["id"] ?? 0);
    }

    /**
     * @return TaskEntity[]
     */
    private function getTaskEntities(array $rows): array
    {
        return array_map(fn(array $row) => TaskEntity::fromPrimitives($row), $rows);
    }
}
