import { AbstractInputDto } from "#app/Modules/Shared/Application/AbstractInputDto.ts";
import { getInt, getTrimmedString, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

import { TasksSearchValidator } from "#app/Modules/Tasks/Domain/Services/TasksSearchValidator.ts";

export class SearchTasksDto extends AbstractInputDto {

    readonly #authUserId: number;
    readonly #authUserUuid: string;
    readonly #userUuid: string;
    readonly #taskText: string;
    readonly #taskStatus: string;
    readonly #taskType: string;
    readonly #targetDateFrom: string;
    readonly #targetDateTo: string;
    readonly #page: number;
    readonly #pageSize: number;

    /**
     * userUuid vacio = las del usuario autenticado. Sin page/pageSize: primera pagina con el tamaño por defecto.
     */
    constructor(primitives: Primitives) {
        super(primitives);

        this.#authUserId = getInt(primitives.authUserId ?? 0);
        this.#authUserUuid = getTrimmedString(primitives.authUserUuid);
        this.#userUuid = getTrimmedString(primitives.userUuid);
        this.#taskText = getTrimmedString(primitives.taskText);
        this.#taskStatus = getTrimmedString(primitives.taskStatus);
        this.#taskType = getTrimmedString(primitives.taskType);
        this.#targetDateFrom = getTrimmedString(primitives.targetDateFrom);
        this.#targetDateTo = getTrimmedString(primitives.targetDateTo);
        this.#page = getInt(primitives.page ?? TasksSearchValidator.PAGE_MIN);
        this.#pageSize = getInt(primitives.pageSize ?? TasksSearchValidator.PAGE_SIZE_DEFAULT);
    }

    static fromPrimitives(primitives: Primitives): SearchTasksDto {
        return new SearchTasksDto(primitives);
    }

    getAuthUserId(): number {
        return this.#authUserId;
    }

    getAuthUserUuid(): string {
        return this.#authUserUuid;
    }

    getUserUuid(): string {
        return this.#userUuid;
    }

    getTaskText(): string {
        return this.#taskText;
    }

    getTaskStatus(): string {
        return this.#taskStatus;
    }

    getTaskType(): string {
        return this.#taskType;
    }

    getTargetDateFrom(): string {
        return this.#targetDateFrom;
    }

    getTargetDateTo(): string {
        return this.#targetDateTo;
    }

    getPage(): number {
        return this.#page;
    }

    getPageSize(): number {
        return this.#pageSize;
    }
}
