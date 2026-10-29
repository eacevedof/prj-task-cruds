import { getInt, type Primitives } from "#app/Modules/Shared/Application/Primitives.ts";

import { TasksKeyEnum } from "#app/Modules/Tasks/Domain/Enums/TasksKeyEnum.ts";

export class SearchTasksResultDto {

    // la pagina pedida: lista de TaskEntity.toPrimitives
    readonly #tasks: Primitives[];
    readonly #page: number;
    readonly #pageSize: number;
    readonly #totalItems: number;
    readonly #totalPages: number;

    constructor(primitives: Primitives) {
        this.#tasks = Array.isArray(primitives.tasks) ? primitives.tasks : [];
        this.#page = getInt(primitives.page ?? 0);
        this.#pageSize = getInt(primitives.pageSize ?? 0);
        this.#totalItems = getInt(primitives.totalItems ?? 0);
        this.#totalPages = getInt(primitives.totalPages ?? 0);
    }

    static fromPrimitives(primitives: Primitives): SearchTasksResultDto {
        return new SearchTasksResultDto(primitives);
    }

    getTasks(): Primitives[] {
        return this.#tasks;
    }

    getPage(): number {
        return this.#page;
    }

    getPageSize(): number {
        return this.#pageSize;
    }

    getTotalItems(): number {
        return this.#totalItems;
    }

    getTotalPages(): number {
        return this.#totalPages;
    }

    /**
     * Tipo TaskPage de tasks.graphql.
     */
    toArray(): Primitives {
        return {
            [TasksKeyEnum.ITEMS]: this.#tasks,
            [TasksKeyEnum.PAGE]: this.#page,
            [TasksKeyEnum.PAGE_SIZE]: this.#pageSize,
            [TasksKeyEnum.TOTAL_ITEMS]: this.#totalItems,
            [TasksKeyEnum.TOTAL_PAGES]: this.#totalPages,
        };
    }
}
