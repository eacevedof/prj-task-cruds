package modules.tasks.infrastructure.repositories;

import java.util.ArrayList;
import java.util.List;
import java.util.Map;

import modules.shared.infrastructure.repositories.AbstractSqliteRepository;
import modules.tasks.domain.entities.TaskEntity;

import static modules.shared.application.Primitives.getInt;

public final class TasksReaderSqliteRepository extends AbstractSqliteRepository {

    // la mas reciente primero en todos los listados
    private static final String ORDER_BY_NEWEST = "ORDER BY t.id DESC";

    private record FiltersSqlAndParams(String filtersSql, List<Object> filtersParams) {
    }

    public static TasksReaderSqliteRepository getInstance() {
        return new TasksReaderSqliteRepository();
    }

    /**
     * Una pagina de las tasks del usuario que cumplen los filtros (vacio = sin filtro).
     */
    public List<TaskEntity> getTasksByUserIdAndFilters(
        int userId,
        String taskText,
        String taskStatus,
        String taskType,
        String targetDateFrom,
        String targetDateTo,
        int limit,
        int offset
    ) {
        FiltersSqlAndParams filtersSqlAndParams = getFiltersSqlAndParams(taskText, taskStatus, taskType, targetDateFrom, targetDateTo);
        String sql = """
            -- getTasksByUserIdAndFilters
            SELECT
                t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
                t.target_date, t.task_description, t.created_at, t.updated_at
            FROM `tasks` t
            INNER JOIN `users` u
            ON u.id = t.user_id
            WHERE 1=1
            AND t.user_id = %d
            %s
            %s
            LIMIT %d OFFSET %d
            """.formatted(userId, filtersSqlAndParams.filtersSql(), ORDER_BY_NEWEST, limit, offset);
        List<TaskEntity> taskEntities = new ArrayList<>();
        for (Map<String, Object> row : query(sql, filtersSqlAndParams.filtersParams().toArray())) {
            taskEntities.add(TaskEntity.fromPrimitives(row));
        }
        return taskEntities;
    }

    public int getTasksCountByUserIdAndFilters(
        int userId,
        String taskText,
        String taskStatus,
        String taskType,
        String targetDateFrom,
        String targetDateTo
    ) {
        FiltersSqlAndParams filtersSqlAndParams = getFiltersSqlAndParams(taskText, taskStatus, taskType, targetDateFrom, targetDateTo);
        String sql = """
            -- getTasksCountByUserIdAndFilters
            SELECT
                COUNT(*) AS n_rows
            FROM `tasks` t
            WHERE 1=1
            AND t.user_id = %d
            %s
            """.formatted(userId, filtersSqlAndParams.filtersSql());
        List<Map<String, Object>> rows = query(sql, filtersSqlAndParams.filtersParams().toArray());
        return rows.isEmpty() ? 0 : getInt(rows.getFirst().get("n_rows"));
    }

    public TaskEntity getTaskById(int taskId) {
        String sql = """
            -- getTaskById
            SELECT
                t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
                t.target_date, t.task_description, t.created_at, t.updated_at
            FROM `tasks` t
            INNER JOIN `users` u
            ON u.id = t.user_id
            WHERE 1=1
            AND t.id = %d
            """.formatted(taskId);
        List<Map<String, Object>> rows = query(sql);
        return rows.isEmpty() ? null : TaskEntity.fromPrimitives(rows.getFirst());
    }

    public TaskEntity getTaskByTaskUuid(String taskUuid) {
        String sql = """
            -- getTaskByTaskUuid
            SELECT
                t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
                t.target_date, t.task_description, t.created_at, t.updated_at
            FROM `tasks` t
            INNER JOIN `users` u
            ON u.id = t.user_id
            WHERE 1=1
            AND t.task_uuid = ?
            """;
        List<Map<String, Object>> rows = query(sql, taskUuid);
        return rows.isEmpty() ? null : TaskEntity.fromPrimitives(rows.getFirst());
    }

    /**
     * AND de los filtros que vienen informados, con parametros enlazados (alias t = tasks).
     * Texto: en titulo o descripcion, sin distinguir mayusculas (LIKE de SQLite) y con %, _ y \ escapados
     * para que se busquen tal cual. Fechas: sobre target_date, ambos extremos incluidos (NULL no entra).
     */
    private FiltersSqlAndParams getFiltersSqlAndParams(
        String taskText,
        String taskStatus,
        String taskType,
        String targetDateFrom,
        String targetDateTo
    ) {
        List<String> conditions = new ArrayList<>();
        List<Object> params = new ArrayList<>();
        if (!taskText.isEmpty()) {
            String likeText = "%" + taskText.replaceAll("[\\\\%_]", "\\\\$0") + "%";
            conditions.add("AND (t.task_title LIKE ? ESCAPE '\\' OR IFNULL(t.task_description, '') LIKE ? ESCAPE '\\')");
            params.add(likeText);
            params.add(likeText);
        }
        if (!taskStatus.isEmpty()) {
            conditions.add("AND t.task_status = ?");
            params.add(taskStatus);
        }
        if (!taskType.isEmpty()) {
            conditions.add("AND t.task_type = ?");
            params.add(taskType);
        }
        if (!targetDateFrom.isEmpty()) {
            conditions.add("AND t.target_date >= ?");
            params.add(targetDateFrom);
        }
        if (!targetDateTo.isEmpty()) {
            conditions.add("AND t.target_date <= ?");
            params.add(targetDateTo);
        }
        return new FiltersSqlAndParams(String.join("\n", conditions), params);
    }
}
