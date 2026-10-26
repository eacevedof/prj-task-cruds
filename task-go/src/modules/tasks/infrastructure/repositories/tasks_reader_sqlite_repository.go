package repositories

import (
	"fmt"
	"strings"

	"task-go/src/modules/shared/application"
	sharedrepositories "task-go/src/modules/shared/infrastructure/repositories"
	"task-go/src/modules/tasks/domain/entities"
)

// la mas reciente primero en todos los listados
const ORDER_BY_NEWEST = "ORDER BY t.id DESC"

type TasksReaderSqliteRepository struct {
	sharedrepositories.AbstractSqliteRepository
}

func TasksReaderSqliteRepositoryGetInstance() *TasksReaderSqliteRepository {
	return &TasksReaderSqliteRepository{AbstractSqliteRepository: sharedrepositories.NewAbstractSqliteRepository()}
}

// GetTasksByUserIdAndFilters una pagina de las tasks del usuario que cumplen los filtros (vacio = sin filtro).
func (r *TasksReaderSqliteRepository) GetTasksByUserIdAndFilters(
	userId int,
	taskText string,
	taskStatus string,
	taskType string,
	targetDateFrom string,
	targetDateTo string,
	limit int,
	offset int,
) ([]*entities.TaskEntity, error) {
	filtersSql, filtersParams := r.getFiltersSqlAndParams(taskText, taskStatus, taskType, targetDateFrom, targetDateTo)
	sql := fmt.Sprintf(`
        -- getTasksByUserIdAndFilters
        SELECT
            t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
            t.target_date, t.task_description, t.created_at, t.updated_at
        FROM `+"`tasks`"+` t
        INNER JOIN `+"`users`"+` u
        ON u.id = t.user_id
        WHERE 1=1
        AND t.user_id = %d
        %s
        %s
        LIMIT %d OFFSET %d
        `, userId, filtersSql, ORDER_BY_NEWEST, limit, offset)
	rows, err := r.Query(sql, filtersParams...)
	if err != nil {
		return nil, err
	}
	taskEntities := []*entities.TaskEntity{}
	for _, row := range rows {
		taskEntities = append(taskEntities, entities.TaskEntityFromPrimitives(row))
	}
	return taskEntities, nil
}

func (r *TasksReaderSqliteRepository) GetTasksCountByUserIdAndFilters(
	userId int,
	taskText string,
	taskStatus string,
	taskType string,
	targetDateFrom string,
	targetDateTo string,
) (int, error) {
	filtersSql, filtersParams := r.getFiltersSqlAndParams(taskText, taskStatus, taskType, targetDateFrom, targetDateTo)
	sql := fmt.Sprintf(`
        -- getTasksCountByUserIdAndFilters
        SELECT
            COUNT(*) AS n_rows
        FROM `+"`tasks`"+` t
        WHERE 1=1
        AND t.user_id = %d
        %s
        `, userId, filtersSql)
	rows, err := r.Query(sql, filtersParams...)
	if err != nil || len(rows) == 0 {
		return 0, err
	}
	return application.ToInt(rows[0]["n_rows"]), nil
}

func (r *TasksReaderSqliteRepository) GetTaskById(taskId int) (*entities.TaskEntity, error) {
	sql := fmt.Sprintf(`
        -- getTaskById
        SELECT
            t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
            t.target_date, t.task_description, t.created_at, t.updated_at
        FROM `+"`tasks`"+` t
        INNER JOIN `+"`users`"+` u
        ON u.id = t.user_id
        WHERE 1=1
        AND t.id = %d
        `, taskId)
	return r.getFirstTaskEntity(r.Query(sql))
}

func (r *TasksReaderSqliteRepository) GetTaskByTaskUuid(taskUuid string) (*entities.TaskEntity, error) {
	sql := `
        -- getTaskByTaskUuid
        SELECT
            t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
            t.target_date, t.task_description, t.created_at, t.updated_at
        FROM ` + "`tasks`" + ` t
        INNER JOIN ` + "`users`" + ` u
        ON u.id = t.user_id
        WHERE 1=1
        AND t.task_uuid = ?
        `
	return r.getFirstTaskEntity(r.Query(sql, taskUuid))
}

// getFiltersSqlAndParams AND de los filtros que vienen informados, con parametros enlazados (alias t = tasks).
// Texto: en titulo o descripcion, sin distinguir mayusculas (LIKE de SQLite) y con %, _ y \ escapados para que se
// busquen tal cual. Fechas: sobre target_date, ambos extremos incluidos (NULL no entra).
func (r *TasksReaderSqliteRepository) getFiltersSqlAndParams(
	taskText string,
	taskStatus string,
	taskType string,
	targetDateFrom string,
	targetDateTo string,
) (string, []any) {
	var conditions []string
	var params []any
	if taskText != "" {
		likeText := "%" + strings.NewReplacer(`\`, `\\`, "%", `\%`, "_", `\_`).Replace(taskText) + "%"
		conditions = append(conditions, `AND (t.task_title LIKE ? ESCAPE '\' OR IFNULL(t.task_description, '') LIKE ? ESCAPE '\')`)
		params = append(params, likeText, likeText)
	}
	if taskStatus != "" {
		conditions = append(conditions, "AND t.task_status = ?")
		params = append(params, taskStatus)
	}
	if taskType != "" {
		conditions = append(conditions, "AND t.task_type = ?")
		params = append(params, taskType)
	}
	if targetDateFrom != "" {
		conditions = append(conditions, "AND t.target_date >= ?")
		params = append(params, targetDateFrom)
	}
	if targetDateTo != "" {
		conditions = append(conditions, "AND t.target_date <= ?")
		params = append(params, targetDateTo)
	}
	return strings.Join(conditions, "\n        "), params
}

func (r *TasksReaderSqliteRepository) getFirstTaskEntity(rows []map[string]any, err error) (*entities.TaskEntity, error) {
	if err != nil || len(rows) == 0 {
		return nil, err
	}
	return entities.TaskEntityFromPrimitives(rows[0]), nil
}
