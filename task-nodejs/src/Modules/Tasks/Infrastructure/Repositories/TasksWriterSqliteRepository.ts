import { AbstractSqliteRepository } from "#app/Modules/Shared/Infrastructure/Repositories/AbstractSqliteRepository.ts";

import type { TaskEntity } from "#app/Modules/Tasks/Domain/Entities/TaskEntity.ts";

export class TasksWriterSqliteRepository extends AbstractSqliteRepository {

    static getInstance(): TasksWriterSqliteRepository {
        return new TasksWriterSqliteRepository();
    }

    /**
     * target_date / task_description vacios se guardan como NULL. En el alta updated_at = created_at.
     */
    insertTask(taskEntity: TaskEntity): number {
        const sql = `
        -- insertTask
        INSERT INTO \`tasks\`
        (user_id, task_uuid, task_title, task_status, task_type, target_date, task_description, created_at, updated_at)
        VALUES
        (${taskEntity.getUserId()}, ?, ?, ?, ?, ?, ?, ?, ?)
        `;
        this.execute(sql, [
            taskEntity.getTaskUuid(),
            taskEntity.getTaskTitle(),
            taskEntity.getTaskStatus(),
            taskEntity.getTaskType(),
            taskEntity.getTargetDate() || null,
            taskEntity.getTaskDescription() || null,
            taskEntity.getCreatedAt(),
            taskEntity.getCreatedAt(),
        ]);
        return this.lastInsertId();
    }

    /**
     * El propietario y created_at no cambian: solo los campos editables y updated_at.
     */
    updateTaskById(taskEntity: TaskEntity): number {
        const sql = `
        -- updateTaskById
        UPDATE \`tasks\`
        SET
            task_title = ?,
            task_status = ?,
            task_type = ?,
            target_date = ?,
            task_description = ?,
            updated_at = ?
        WHERE 1=1
        AND id = ?
        `;
        return this.execute(sql, [
            taskEntity.getTaskTitle(),
            taskEntity.getTaskStatus(),
            taskEntity.getTaskType(),
            taskEntity.getTargetDate() || null,
            taskEntity.getTaskDescription() || null,
            taskEntity.getUpdatedAt(),
            taskEntity.getId(),
        ]);
    }

    deleteTaskById(taskEntity: TaskEntity): number {
        const sql = `
        -- deleteTaskById
        DELETE FROM \`tasks\`
        WHERE 1=1
        AND id = ${taskEntity.getId()}
        `;
        return this.execute(sql);
    }
}
