import { __ } from "#app/Core/config/global.ts";

import type { GetTaskDto } from "#app/Modules/Tasks/Application/GetTask/GetTaskDto.ts";
import { GetTaskResultDto } from "#app/Modules/Tasks/Application/GetTask/GetTaskResultDto.ts";
import type { TaskEntity } from "#app/Modules/Tasks/Domain/Entities/TaskEntity.ts";
import { TasksMessageEnum } from "#app/Modules/Tasks/Domain/Enums/TasksMessageEnum.ts";
import { TasksReaderSqliteRepository } from "#app/Modules/Tasks/Infrastructure/Repositories/TasksReaderSqliteRepository.ts";
import { TasksException } from "#app/Modules/Tasks/Domain/Exceptions/TasksException.ts";

export class GetTaskService {

    readonly #tasksReaderSqliteRepository: TasksReaderSqliteRepository;

    #getTaskDto!: GetTaskDto;

    constructor() {
        this.#tasksReaderSqliteRepository = TasksReaderSqliteRepository.getInstance();
    }

    static getInstance(): GetTaskService {
        return new GetTaskService();
    }

    /**
     * @throws TasksException
     */
    invoke(getTaskDto: GetTaskDto): GetTaskResultDto {
        this.#getTaskDto = getTaskDto;
        this.#failIfWrongInput();

        return GetTaskResultDto.fromPrimitives({ task: this.#tryToGetOwnTaskOrFail().toPrimitives() });
    }

    /**
     * @throws TasksException
     */
    #failIfWrongInput(): void {
        if (this.#getTaskDto.getAuthUserId() < 1) {
            TasksException.unauthorizedCustom(__(TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
    }

    /**
     * Una task de otro usuario responde igual que si no existiera (no se revela que existe).
     * @throws TasksException
     */
    #tryToGetOwnTaskOrFail(): TaskEntity {
        const taskUuid = this.#getTaskDto.getTaskUuid();
        const taskEntity = this.#tasksReaderSqliteRepository.getTaskByTaskUuid(taskUuid);
        if (!taskEntity || taskEntity.getUserId() !== this.#getTaskDto.getAuthUserId()) {
            TasksException.notFoundCustom(__(TasksMessageEnum.TASK_X_NOT_FOUND, taskUuid));
        }
        return taskEntity;
    }
}
