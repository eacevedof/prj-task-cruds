import { getInt, getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

import { TasksKeyEnum } from "#app/Modules/Tasks/Domain/Enums/TasksKeyEnum.ts";

export class TaskEntity {

    static readonly TASK_UUID_PREFIX = "task-";

    readonly #id: number | null;
    readonly #userId: number | null;
    readonly #userUuid: string;
    readonly #taskUuid: string;
    readonly #taskTitle: string;
    readonly #taskStatus: string;
    readonly #taskType: string;
    readonly #targetDate: string;
    readonly #taskDescription: string;
    readonly #createdAt: string;
    readonly #updatedAt: string;

    /**
     * Desde una fila de tasks JOIN users (user_uuid viene del usuario propietario; columnas en snake_case:
     * contrato con la BD). target_date / task_description NULL en BD -> "" aqui (neutro de string).
     */
    constructor(primitives: Primitives) {
        this.#id = primitives[TasksKeyEnum.ID] != null ? getInt(primitives[TasksKeyEnum.ID]) : null;
        this.#userId = primitives[TasksKeyEnum.USER_ID] != null ? getInt(primitives[TasksKeyEnum.USER_ID]) : null;
        this.#userUuid = getTrimmedString(primitives[TasksKeyEnum.USER_UUID]);
        this.#taskUuid = getTrimmedString(primitives[TasksKeyEnum.TASK_UUID]);
        this.#taskTitle = getTrimmedString(primitives[TasksKeyEnum.TASK_TITLE]);
        this.#taskStatus = getTrimmedString(primitives[TasksKeyEnum.TASK_STATUS]);
        this.#taskType = getTrimmedString(primitives[TasksKeyEnum.TASK_TYPE]);
        this.#targetDate = getTrimmedString(primitives[TasksKeyEnum.TARGET_DATE]);
        this.#taskDescription = getTrimmedString(primitives[TasksKeyEnum.TASK_DESCRIPTION]);
        this.#createdAt = getTrimmedString(primitives[TasksKeyEnum.CREATED_AT]);
        this.#updatedAt = getTrimmedString(primitives[TasksKeyEnum.UPDATED_AT]);
    }

    static fromPrimitives(primitives: Primitives): TaskEntity {
        return new TaskEntity(primitives);
    }

    /**
     * Representacion publica (API): el identificador para operar es task_uuid; task_id es solo informativo
     * (orden, referencia legible). El propietario va por user_uuid, nunca por su id interno.
     * Los opcionales vacios salen como null (contrato de la API).
     */
    toPrimitives(): Primitives {
        return {
            [TasksKeyEnum.TASK_ID]: this.#id,
            [TasksKeyEnum.TASK_UUID]: this.#taskUuid,
            [TasksKeyEnum.USER_UUID]: this.#userUuid,
            [TasksKeyEnum.TASK_TITLE]: this.#taskTitle,
            [TasksKeyEnum.TASK_STATUS]: this.#taskStatus,
            [TasksKeyEnum.TASK_TYPE]: this.#taskType,
            [TasksKeyEnum.TARGET_DATE]: this.#targetDate || null,
            [TasksKeyEnum.TASK_DESCRIPTION]: this.#taskDescription || null,
            [TasksKeyEnum.CREATED_AT]: this.#createdAt,
            [TasksKeyEnum.UPDATED_AT]: this.#updatedAt,
        };
    }

    getId(): number | null {
        return this.#id;
    }

    getUserId(): number | null {
        return this.#userId;
    }

    getUserUuid(): string {
        return this.#userUuid;
    }

    getTaskUuid(): string {
        return this.#taskUuid;
    }

    getTaskTitle(): string {
        return this.#taskTitle;
    }

    getTaskStatus(): string {
        return this.#taskStatus;
    }

    getTaskType(): string {
        return this.#taskType;
    }

    getTargetDate(): string {
        return this.#targetDate;
    }

    getTaskDescription(): string {
        return this.#taskDescription;
    }

    getCreatedAt(): string {
        return this.#createdAt;
    }

    getUpdatedAt(): string {
        return this.#updatedAt;
    }
}
