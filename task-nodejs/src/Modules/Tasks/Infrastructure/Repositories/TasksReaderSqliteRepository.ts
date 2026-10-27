import type { SQLInputValue } from "node:sqlite";

import { getInt } from "#app/Modules/Shared/Application/Primitives.ts";
import { AbstractSqliteRepository } from "#app/Modules/Shared/Infrastructure/Repositories/AbstractSqliteRepository.ts";

import { TaskEntity } from "#app/Modules/Tasks/Domain/Entities/TaskEntity.ts";

// la mas reciente primero en todos los listados
const ORDER_BY_NEWEST = "ORDER BY t.id DESC";

export class TasksReaderSqliteRepository extends AbstractSqliteRepository {

    static getInstance(): TasksReaderSqliteRepository {
        return new TasksReaderSqliteRepository();
    }

    /**
     * Una pagina de las tasks del usuario que cumplen los filtros (vacio = sin filtro).
     */
    getTasksByUserIdAndFilters(
        userId: number,
        taskText: string,
        taskStatus: string,
        taskType: string,
        targetDateFrom: string,
        targetDateTo: string,
        limit: number,
        offset: number
    ): TaskEntity[] {
        const [filtersSql, filtersParams] = this.#getFiltersSqlAndParams(taskText, taskStatus, taskType, targetDateFrom, targetDateTo);
        const sql = `
        -- getTasksByUserIdAndFilters
        SELECT
            t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
            t.target_date, t.task_description, t.created_at, t.updated_at
        FROM \`tasks\` t
        INNER JOIN \`users\` u
        ON u.id = t.user_id
        WHERE 1=1
        AND t.user_id = ${userId}
        ${filtersSql}
        ${ORDER_BY_NEWEST}
        LIMIT ${limit} OFFSET ${offset}
        `;
        return this.query(sql, filtersParams).map((row) => TaskEntity.fromPrimitives(row));
    }

    getTasksCountByUserIdAndFilters(
        userId: number,
        taskText: string,
        taskStatus: string,
        taskType: string,
        targetDateFrom: string,
        targetDateTo: string
    ): number {
        const [filtersSql, filtersParams] = this.#getFiltersSqlAndParams(taskText, taskStatus, taskType, targetDateFrom, targetDateTo);
        const sql = `
        -- getTasksCountByUserIdAndFilters
        SELECT
            COUNT(*) AS n_rows
        FROM \`tasks\` t
        WHERE 1=1
        AND t.user_id = ${userId}
        ${filtersSql}
        `;
        const rows = this.query(sql, filtersParams);
        return getInt(rows[0]?.n_rows ?? 0);
    }

    getTaskById(taskId: number): TaskEntity | null {
        const sql = `
        -- getTaskById
        SELECT
            t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
            t.target_date, t.task_description, t.created_at, t.updated_at
        FROM \`tasks\` t
        INNER JOIN \`users\` u
        ON u.id = t.user_id
        WHERE 1=1
        AND t.id = ${taskId}
        `;
        const rows = this.query(sql);
        return rows.length ? TaskEntity.fromPrimitives(rows[0]) : null;
    }

    getTaskByTaskUuid(taskUuid: string): TaskEntity | null {
        const sql = `
        -- getTaskByTaskUuid
        SELECT
            t.id, t.user_id, u.user_uuid, t.task_uuid, t.task_title, t.task_status, t.task_type,
            t.target_date, t.task_description, t.created_at, t.updated_at
        FROM \`tasks\` t
        INNER JOIN \`users\` u
        ON u.id = t.user_id
        WHERE 1=1
        AND t.task_uuid = ?
        `;
        const rows = this.query(sql, [taskUuid]);
        return rows.length ? TaskEntity.fromPrimitives(rows[0]) : null;
    }

    /**
     * AND de los filtros que vienen informados, con parametros enlazados (alias t = tasks).
     * Texto: en titulo o descripcion, sin distinguir mayusculas (LIKE de SQLite) y con %, _ y \ escapados
     * para que se busquen tal cual. Fechas: sobre target_date, ambos extremos incluidos (NULL no entra).
     */
    #getFiltersSqlAndParams(
        taskText: string,
        taskStatus: string,
        taskType: string,
        targetDateFrom: string,
        targetDateTo: string
    ): [string, SQLInputValue[]] {
        const conditions: string[] = [];
        const params: SQLInputValue[] = [];
        if (taskText !== "") {
            const likeText = `%${taskText.replace(/[\\%_]/g, "\\$&")}%`;
            conditions.push("AND (t.task_title LIKE ? ESCAPE '\\' OR IFNULL(t.task_description, '') LIKE ? ESCAPE '\\')");
            params.push(likeText, likeText);
        }
        if (taskStatus !== "") {
            conditions.push("AND t.task_status = ?");
            params.push(taskStatus);
        }
        if (taskType !== "") {
            conditions.push("AND t.task_type = ?");
            params.push(taskType);
        }
        if (targetDateFrom !== "") {
            conditions.push("AND t.target_date >= ?");
            params.push(targetDateFrom);
        }
        if (targetDateTo !== "") {
            conditions.push("AND t.target_date <= ?");
            params.push(targetDateTo);
        }
        return [conditions.join("\n        "), params];
    }
}
