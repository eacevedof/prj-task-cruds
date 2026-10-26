package repositories

import (
	"fmt"

	sharedrepositories "task-go/src/modules/shared/infrastructure/repositories"
	"task-go/src/modules/tasks/domain/entities"
)

type TasksWriterSqliteRepository struct {
	sharedrepositories.AbstractSqliteRepository
}

func TasksWriterSqliteRepositoryGetInstance() *TasksWriterSqliteRepository {
	return &TasksWriterSqliteRepository{AbstractSqliteRepository: sharedrepositories.NewAbstractSqliteRepository()}
}

// InsertTask target_date / task_description vacios se guardan como NULL. En el alta updated_at = created_at.
func (r *TasksWriterSqliteRepository) InsertTask(taskEntity *entities.TaskEntity) (int, error) {
	sql := fmt.Sprintf(`
        -- insertTask
        INSERT INTO `+"`tasks`"+`
        (user_id, task_uuid, task_title, task_status, task_type, target_date, task_description, created_at, updated_at)
        VALUES
        (%d, ?, ?, ?, ?, ?, ?, ?, ?)
        `, taskEntity.GetUserId())
	_, err := r.Execute(sql,
		taskEntity.GetTaskUuid(),
		taskEntity.GetTaskTitle(),
		taskEntity.GetTaskStatus(),
		taskEntity.GetTaskType(),
		getNullIfEmpty(taskEntity.GetTargetDate()),
		getNullIfEmpty(taskEntity.GetTaskDescription()),
		taskEntity.GetCreatedAt(),
		taskEntity.GetCreatedAt(),
	)
	if err != nil {
		return 0, err
	}
	return r.GetLastInsertId(), nil
}

// UpdateTaskById el propietario y created_at no cambian: solo los campos editables y updated_at.
func (r *TasksWriterSqliteRepository) UpdateTaskById(taskEntity *entities.TaskEntity) (int, error) {
	sql := `
        -- updateTaskById
        UPDATE ` + "`tasks`" + `
        SET
            task_title = ?,
            task_status = ?,
            task_type = ?,
            target_date = ?,
            task_description = ?,
            updated_at = ?
        WHERE 1=1
        AND id = ?
        `
	return r.Execute(sql,
		taskEntity.GetTaskTitle(),
		taskEntity.GetTaskStatus(),
		taskEntity.GetTaskType(),
		getNullIfEmpty(taskEntity.GetTargetDate()),
		getNullIfEmpty(taskEntity.GetTaskDescription()),
		taskEntity.GetUpdatedAt(),
		taskEntity.GetId(),
	)
}

func (r *TasksWriterSqliteRepository) DeleteTaskById(taskEntity *entities.TaskEntity) (int, error) {
	sql := fmt.Sprintf(`
        -- deleteTaskById
        DELETE FROM `+"`tasks`"+`
        WHERE 1=1
        AND id = %d
        `, taskEntity.GetId())
	return r.Execute(sql)
}

// getNullIfEmpty el $valor ?: null de task-php: "" se guarda como NULL.
func getNullIfEmpty(value string) any {
	if value == "" {
		return nil
	}
	return value
}
