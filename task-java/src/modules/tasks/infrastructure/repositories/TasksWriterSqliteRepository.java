package modules.tasks.infrastructure.repositories;

import modules.shared.infrastructure.repositories.AbstractSqliteRepository;
import modules.tasks.domain.entities.TaskEntity;

public final class TasksWriterSqliteRepository extends AbstractSqliteRepository {

    public static TasksWriterSqliteRepository getInstance() {
        return new TasksWriterSqliteRepository();
    }

    /**
     * target_date / task_description vacios se guardan como NULL. En el alta updated_at = created_at.
     */
    public int insertTask(TaskEntity taskEntity) {
        String sql = """
            -- insertTask
            INSERT INTO `tasks`
            (user_id, task_uuid, task_title, task_status, task_type, target_date, task_description, created_at, updated_at)
            VALUES
            (%d, ?, ?, ?, ?, ?, ?, ?, ?)
            """.formatted(taskEntity.getUserId());
        execute(sql,
            taskEntity.getTaskUuid(),
            taskEntity.getTaskTitle(),
            taskEntity.getTaskStatus(),
            taskEntity.getTaskType(),
            taskEntity.getTargetDate().isEmpty() ? null : taskEntity.getTargetDate(),
            taskEntity.getTaskDescription().isEmpty() ? null : taskEntity.getTaskDescription(),
            taskEntity.getCreatedAt(),
            taskEntity.getCreatedAt()
        );
        return lastInsertId();
    }

    /**
     * El propietario y created_at no cambian: solo los campos editables y updated_at.
     */
    public int updateTaskById(TaskEntity taskEntity) {
        String sql = """
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
            """;
        return execute(sql,
            taskEntity.getTaskTitle(),
            taskEntity.getTaskStatus(),
            taskEntity.getTaskType(),
            taskEntity.getTargetDate().isEmpty() ? null : taskEntity.getTargetDate(),
            taskEntity.getTaskDescription().isEmpty() ? null : taskEntity.getTaskDescription(),
            taskEntity.getUpdatedAt(),
            taskEntity.getId()
        );
    }

    public int deleteTaskById(TaskEntity taskEntity) {
        String sql = """
            -- deleteTaskById
            DELETE FROM `tasks`
            WHERE 1=1
            AND id = %d
            """.formatted(taskEntity.getId());
        return execute(sql);
    }
}
