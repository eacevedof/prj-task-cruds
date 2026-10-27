import { __ } from "#app/Core/config/global.ts";

import { TaskStatusEnum } from "#app/Modules/Tasks/Domain/Enums/TaskStatusEnum.ts";
import { TaskTypeEnum } from "#app/Modules/Tasks/Domain/Enums/TaskTypeEnum.ts";
import { TasksKeyEnum } from "#app/Modules/Tasks/Domain/Enums/TasksKeyEnum.ts";
import { TasksMessageEnum } from "#app/Modules/Tasks/Domain/Enums/TasksMessageEnum.ts";

/**
 * Reglas de una busqueda paginada de tasks. Filtros vacios = sin filtro. Devuelve un error "campo: mensaje"
 * por regla incumplida; vacio = valida.
 */
export class TasksSearchValidator {

    static readonly PAGE_MIN = 1;
    static readonly PAGE_SIZE_MIN = 1;
    static readonly PAGE_SIZE_MAX = 100;
    static readonly PAGE_SIZE_DEFAULT = 20;
    static readonly TARGET_DATE_FORMAT = "YYYY-MM-DD";

    static getInstance(): TasksSearchValidator {
        return new TasksSearchValidator();
    }

    getErrors(
        taskStatus: string,
        taskType: string,
        targetDateFrom: string,
        targetDateTo: string,
        page: number,
        pageSize: number
    ): string[] {
        const errors: string[] = [];
        if (taskStatus !== "" && !TaskStatusEnum.VALUES.includes(taskStatus)) {
            errors.push(`${TasksKeyEnum.TASK_STATUS}: ${__(TasksMessageEnum.ALLOWED_VALUES_ARE_X, TaskStatusEnum.VALUES.join(", "))}`);
        }
        if (taskType !== "" && !TaskTypeEnum.VALUES.includes(taskType)) {
            errors.push(`${TasksKeyEnum.TASK_TYPE}: ${__(TasksMessageEnum.ALLOWED_VALUES_ARE_X, TaskTypeEnum.VALUES.join(", "))}`);
        }

        const isTargetDateFromValid = targetDateFrom === "" || this.#isValidDate(targetDateFrom);
        if (!isTargetDateFromValid) {
            errors.push(`${TasksKeyEnum.TARGET_DATE_FROM}: ${__(TasksMessageEnum.INVALID_DATE_FORMAT_X, TasksSearchValidator.TARGET_DATE_FORMAT)}`);
        }
        const isTargetDateToValid = targetDateTo === "" || this.#isValidDate(targetDateTo);
        if (!isTargetDateToValid) {
            errors.push(`${TasksKeyEnum.TARGET_DATE_TO}: ${__(TasksMessageEnum.INVALID_DATE_FORMAT_X, TasksSearchValidator.TARGET_DATE_FORMAT)}`);
        }
        // fechas ISO validas: comparar el texto es comparar la fecha
        if (targetDateFrom && targetDateTo && isTargetDateFromValid && isTargetDateToValid && targetDateTo < targetDateFrom) {
            errors.push(`${TasksKeyEnum.TARGET_DATE_TO}: ${__(TasksMessageEnum.DATE_TO_BEFORE_DATE_FROM)}`);
        }

        if (page < TasksSearchValidator.PAGE_MIN) {
            errors.push(`${TasksKeyEnum.PAGE}: ${__(TasksMessageEnum.MIN_VALUE_IS_X, TasksSearchValidator.PAGE_MIN)}`);
        }
        if (pageSize < TasksSearchValidator.PAGE_SIZE_MIN || pageSize > TasksSearchValidator.PAGE_SIZE_MAX) {
            errors.push(`${TasksKeyEnum.PAGE_SIZE}: ${__(TasksMessageEnum.VALUE_BETWEEN_X_AND_X, TasksSearchValidator.PAGE_SIZE_MIN, TasksSearchValidator.PAGE_SIZE_MAX)}`);
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
