import { __ } from "#app/Core/config/global.ts";

import type { TaskEntity } from "#app/Modules/Tasks/Domain/Entities/TaskEntity.ts";
import { TaskStatusEnum } from "#app/Modules/Tasks/Domain/Enums/TaskStatusEnum.ts";
import { TaskTypeEnum } from "#app/Modules/Tasks/Domain/Enums/TaskTypeEnum.ts";
import { TasksMessageEnum } from "#app/Modules/Tasks/Domain/Enums/TasksMessageEnum.ts";

/**
 * Reglas de escritura de una task (alta y modificacion). Recibe la entidad YA con los defaults
 * aplicados (status/type vacios -> DEFAULT). Devuelve un error "campo: mensaje" por regla incumplida.
 */
export class TaskFieldsValidator {

    static readonly TASK_TITLE_MAX_LENGTH = 150;
    static readonly TASK_DESCRIPTION_MAX_LENGTH = 2000;
    static readonly TARGET_DATE_FORMAT = "YYYY-MM-DD";

    static getInstance(): TaskFieldsValidator {
        return new TaskFieldsValidator();
    }

    getErrors(taskEntity: TaskEntity): string[] {
        const errors: string[] = [];
        if (taskEntity.getTaskTitle() === "") {
            errors.push(`task_title: ${__(TasksMessageEnum.FIELD_IS_REQUIRED)}`);
        }
        else if ([...taskEntity.getTaskTitle()].length > TaskFieldsValidator.TASK_TITLE_MAX_LENGTH) {
            errors.push(`task_title: ${__(TasksMessageEnum.MAX_LENGTH_IS_X, TaskFieldsValidator.TASK_TITLE_MAX_LENGTH)}`);
        }

        if (!TaskStatusEnum.VALUES.includes(taskEntity.getTaskStatus())) {
            errors.push(`task_status: ${__(TasksMessageEnum.ALLOWED_VALUES_ARE_X, TaskStatusEnum.VALUES.join(", "))}`);
        }

        if (!TaskTypeEnum.VALUES.includes(taskEntity.getTaskType())) {
            errors.push(`task_type: ${__(TasksMessageEnum.ALLOWED_VALUES_ARE_X, TaskTypeEnum.VALUES.join(", "))}`);
        }

        if (taskEntity.getTargetDate() !== "" && !this.#isValidDate(taskEntity.getTargetDate())) {
            errors.push(`target_date: ${__(TasksMessageEnum.INVALID_DATE_FORMAT_X, TaskFieldsValidator.TARGET_DATE_FORMAT)}`);
        }

        if ([...taskEntity.getTaskDescription()].length > TaskFieldsValidator.TASK_DESCRIPTION_MAX_LENGTH) {
            errors.push(`task_description: ${__(TasksMessageEnum.MAX_LENGTH_IS_X, TaskFieldsValidator.TASK_DESCRIPTION_MAX_LENGTH)}`);
        }
        return errors;
    }

    /**
     * Fecha real y en formato exacto: "2026-02-30" o "2026-1-5" no valen.
     */
    #isValidDate(date: string): boolean {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(date)) return false;

        const dateTime = new Date(`${date}T00:00:00Z`);
        return !Number.isNaN(dateTime.getTime()) && dateTime.toISOString().substring(0, 10) === date;
    }
}
