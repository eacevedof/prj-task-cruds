from Modules.Shared.Infrastructure.Repositories.AbstractSqliteRepository import AbstractSqliteRepository

from Modules.Tasks.Domain.Entities.TaskEntity import TaskEntity


class TasksWriterSqliteRepository(AbstractSqliteRepository):

    @staticmethod
    def getInstance() -> "TasksWriterSqliteRepository":
        return TasksWriterSqliteRepository()

    def insertTask(self, taskEntity: TaskEntity) -> int:
        """
        target_date / task_description vacios se guardan como NULL. En el alta updated_at = created_at.
        """
        sql = f"""
        -- insertTask
        INSERT INTO `tasks`
        (user_id, task_uuid, task_title, task_status, task_type, target_date, task_description, created_at, updated_at)
        VALUES
        ({taskEntity.getUserId()}, ?, ?, ?, ?, ?, ?, ?, ?)
        """
        self.execute(sql, [
            taskEntity.getTaskUuid(),
            taskEntity.getTaskTitle(),
            taskEntity.getTaskStatus(),
            taskEntity.getTaskType(),
            taskEntity.getTargetDate() or None,
            taskEntity.getTaskDescription() or None,
            taskEntity.getCreatedAt(),
            taskEntity.getCreatedAt(),
        ])
        return self.lastInsertId()

    def updateTaskById(self, taskEntity: TaskEntity) -> int:
        """
        El propietario y created_at no cambian: solo los campos editables y updated_at.
        """
        sql = """
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
        """
        return self.execute(sql, [
            taskEntity.getTaskTitle(),
            taskEntity.getTaskStatus(),
            taskEntity.getTaskType(),
            taskEntity.getTargetDate() or None,
            taskEntity.getTaskDescription() or None,
            taskEntity.getUpdatedAt(),
            taskEntity.getId(),
        ])

    def deleteTaskById(self, taskEntity: TaskEntity) -> int:
        sql = f"""
        -- deleteTaskById
        DELETE FROM `tasks`
        WHERE 1=1
        AND id = {taskEntity.getId()}
        """
        return self.execute(sql)
