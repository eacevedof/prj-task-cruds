<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Repositories;

use App\Modules\Shared\Infrastructure\Repositories\AbstractSqliteRepository;

use App\Modules\Tasks\Domain\Entities\TaskEntity;

final class TasksReaderSqliteRepository extends AbstractSqliteRepository
{
    public static function getInstance(): self
    {
        return new self();
    }

    // la mas reciente primero en todos los listados
    private const string ORDER_BY_NEWEST = "ORDER BY t.id DESC";

    /**
     * @return TaskEntity[]
     */
    public function getTasksByUserId(int $userId): array
    {
        $orderBy = self::ORDER_BY_NEWEST;
        $sql = "
        -- getTasksByUserId
        SELECT
            t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
            t.target_date, t.task_description, t.created_at, t.updated_at
        FROM `tasks` t
        INNER JOIN `users` u
        ON u.id = t.user_id
        WHERE 1=1
        AND t.user_id = {$userId}
        {$orderBy}
        ";
        return array_map(
            fn(array $row) => TaskEntity::fromPrimitives($row),
            $this->query($sql)
        );
    }

    /**
     * Una pagina de las tasks del usuario que cumplen los filtros (vacio = sin filtro).
     * @return TaskEntity[]
     */
    public function getTasksByUserIdAndFilters(
        int $userId,
        string $taskText,
        string $taskStatus,
        string $taskType,
        string $targetDateFrom,
        string $targetDateTo,
        int $limit,
        int $offset
    ): array
    {
        [$filtersSql, $filtersParams] = $this->getFiltersSqlAndParams($taskText, $taskStatus, $taskType, $targetDateFrom, $targetDateTo);
        $orderBy = self::ORDER_BY_NEWEST;
        $sql = "
        -- getTasksByUserIdAndFilters
        SELECT
            t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
            t.target_date, t.task_description, t.created_at, t.updated_at
        FROM `tasks` t
        INNER JOIN `users` u
        ON u.id = t.user_id
        WHERE 1=1
        AND t.user_id = {$userId}
        {$filtersSql}
        {$orderBy}
        LIMIT {$limit} OFFSET {$offset}
        ";
        return array_map(
            fn(array $row) => TaskEntity::fromPrimitives($row),
            $this->query($sql, $filtersParams)
        );
    }

    public function getTasksCountByUserIdAndFilters(
        int $userId,
        string $taskText,
        string $taskStatus,
        string $taskType,
        string $targetDateFrom,
        string $targetDateTo
    ): int
    {
        [$filtersSql, $filtersParams] = $this->getFiltersSqlAndParams($taskText, $taskStatus, $taskType, $targetDateFrom, $targetDateTo);
        $sql = "
        -- getTasksCountByUserIdAndFilters
        SELECT
            COUNT(*) AS n_rows
        FROM `tasks` t
        WHERE 1=1
        AND t.user_id = {$userId}
        {$filtersSql}
        ";
        $rows = $this->query($sql, $filtersParams);
        return (int) ($rows[0]["n_rows"] ?? 0);
    }

    public function getTaskById(int $taskId): ?TaskEntity
    {
        $sql = "
        -- getTaskById
        SELECT
            t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
            t.target_date, t.task_description, t.created_at, t.updated_at
        FROM `tasks` t
        INNER JOIN `users` u
        ON u.id = t.user_id
        WHERE 1=1
        AND t.id = {$taskId}
        ";
        $rows = $this->query($sql);
        return $rows ? TaskEntity::fromPrimitives($rows[0]) : null;
    }

    public function getTaskByTaskUuid(string $taskUuid): ?TaskEntity
    {
        $sql = "
        -- getTaskByTaskUuid
        SELECT
            t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
            t.target_date, t.task_description, t.created_at, t.updated_at
        FROM `tasks` t
        INNER JOIN `users` u
        ON u.id = t.user_id
        WHERE 1=1
        AND t.task_uuid = ?
        ";
        $rows = $this->query($sql, [$taskUuid]);
        return $rows ? TaskEntity::fromPrimitives($rows[0]) : null;
    }

    /**
     * AND de los filtros que vienen informados, con parametros enlazados (alias t = tasks).
     * Texto: en titulo o descripcion, sin distinguir mayusculas (LIKE de SQLite) y con %, _ y \ escapados
     * para que se busquen tal cual. Fechas: sobre target_date, ambos extremos incluidos (NULL no entra).
     * @return array{0: string, 1: array}
     */
    private function getFiltersSqlAndParams(
        string $taskText,
        string $taskStatus,
        string $taskType,
        string $targetDateFrom,
        string $targetDateTo
    ): array
    {
        $conditions = [];
        $params = [];
        if ($taskText !== "") {
            $likeText = "%".str_replace(["\\", "%", "_"], ["\\\\", "\\%", "\\_"], $taskText)."%";
            $conditions[] = "AND (t.task_title LIKE ? ESCAPE '\\' OR IFNULL(t.task_description, '') LIKE ? ESCAPE '\\')";
            array_push($params, $likeText, $likeText);
        }
        if ($taskStatus !== "") {
            $conditions[] = "AND t.task_status = ?";
            $params[] = $taskStatus;
        }
        if ($taskType !== "") {
            $conditions[] = "AND t.task_type = ?";
            $params[] = $taskType;
        }
        if ($targetDateFrom !== "") {
            $conditions[] = "AND t.target_date >= ?";
            $params[] = $targetDateFrom;
        }
        if ($targetDateTo !== "") {
            $conditions[] = "AND t.target_date <= ?";
            $params[] = $targetDateTo;
        }
        return [implode("\n        ", $conditions), $params];
    }
}
