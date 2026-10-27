import { __ } from "#app/Core/config/global.ts";

import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { ErrorStackTrait } from "#app/Modules/Shared/Infrastructure/Traits/ErrorStackTrait.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import type { SearchTasksDto } from "#app/Modules/Tasks/Application/SearchTasks/SearchTasksDto.ts";
import { SearchTasksResultDto } from "#app/Modules/Tasks/Application/SearchTasks/SearchTasksResultDto.ts";
import { TasksMessageEnum } from "#app/Modules/Tasks/Domain/Enums/TasksMessageEnum.ts";
import { TasksSearchValidator } from "#app/Modules/Tasks/Domain/Services/TasksSearchValidator.ts";
import { TasksReaderSqliteRepository } from "#app/Modules/Tasks/Infrastructure/Repositories/TasksReaderSqliteRepository.ts";
import { TasksException } from "#app/Modules/Tasks/Domain/Exceptions/TasksException.ts";

/**
 * Busqueda paginada de las tasks del usuario autenticado (mas reciente primero): filtros por texto, estado,
 * tipo y rango de target_date.
 */
export class SearchTasksService {

    readonly #errorStackTrait: ErrorStackTrait;
    readonly #tasksSearchValidator: TasksSearchValidator;
    readonly #tasksReaderSqliteRepository: TasksReaderSqliteRepository;

    #searchTasksDto!: SearchTasksDto;

    constructor() {
        this.#errorStackTrait = ErrorStackTrait.getInstance();
        this.#tasksSearchValidator = TasksSearchValidator.getInstance();
        this.#tasksReaderSqliteRepository = TasksReaderSqliteRepository.getInstance();
    }

    static getInstance(): SearchTasksService {
        return new SearchTasksService();
    }

    /**
     * @throws FieldsException
     * @throws TasksException
     */
    invoke(searchTasksDto: SearchTasksDto): SearchTasksResultDto {
        this.#searchTasksDto = searchTasksDto;
        this.#failIfWrongInput();
        this.#failIfWrongSearch();

        const userId = this.#searchTasksDto.getAuthUserId();

        const totalItems = this.#tasksReaderSqliteRepository.getTasksCountByUserIdAndFilters(
            userId,
            this.#searchTasksDto.getTaskText(),
            this.#searchTasksDto.getTaskStatus(),
            this.#searchTasksDto.getTaskType(),
            this.#searchTasksDto.getTargetDateFrom(),
            this.#searchTasksDto.getTargetDateTo()
        );
        const page = this.#searchTasksDto.getPage();
        const pageSize = this.#searchTasksDto.getPageSize();
        const taskEntities = this.#tasksReaderSqliteRepository.getTasksByUserIdAndFilters(
            userId,
            this.#searchTasksDto.getTaskText(),
            this.#searchTasksDto.getTaskStatus(),
            this.#searchTasksDto.getTaskType(),
            this.#searchTasksDto.getTargetDateFrom(),
            this.#searchTasksDto.getTargetDateTo(),
            pageSize,
            (page - 1) * pageSize
        );

        return SearchTasksResultDto.fromPrimitives({
            tasks: taskEntities.map((taskEntity) => taskEntity.toPrimitives()),
            page: page,
            pageSize: pageSize,
            totalItems: totalItems,
            totalPages: Math.ceil(totalItems / pageSize),
        });
    }

    /**
     * Sin user_uuid se busca en las del autenticado; con uno ajeno responde igual que si no existiera.
     * @throws TasksException
     */
    #failIfWrongInput(): void {
        if (this.#searchTasksDto.getAuthUserId() < 1) {
            TasksException.unauthorizedCustom(__(TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
        const userUuid = this.#searchTasksDto.getUserUuid() || this.#searchTasksDto.getAuthUserUuid();
        if (userUuid !== this.#searchTasksDto.getAuthUserUuid()) {
            TasksException.notFoundCustom(__(TasksMessageEnum.USER_X_NOT_FOUND, userUuid));
        }
    }

    /**
     * @throws FieldsException
     */
    #failIfWrongSearch(): void {
        const errors = this.#tasksSearchValidator.getErrors(
            this.#searchTasksDto.getTaskStatus(),
            this.#searchTasksDto.getTaskType(),
            this.#searchTasksDto.getTargetDateFrom(),
            this.#searchTasksDto.getTargetDateTo(),
            this.#searchTasksDto.getPage(),
            this.#searchTasksDto.getPageSize()
        );
        if (!errors.length) return;

        FieldsException.badRequestCustom(
            this.#errorStackTrait.getErrorStackMessage(
                __(SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors
            ));
    }
}
