import { __ } from "#app/Core/config/global.ts";

import type { DeleteTaskDto } from "#app/Modules/Tasks/Application/DeleteTask/DeleteTaskDto.ts";
import { DeleteTaskResultDto } from "#app/Modules/Tasks/Application/DeleteTask/DeleteTaskResultDto.ts";
import type { TaskEntity } from "#app/Modules/Tasks/Domain/Entities/TaskEntity.ts";
import { TasksMessageEnum } from "#app/Modules/Tasks/Domain/Enums/TasksMessageEnum.ts";
import { TasksReaderSqliteRepository } from "#app/Modules/Tasks/Infrastructure/Repositories/TasksReaderSqliteRepository.ts";
import { TasksWriterSqliteRepository } from "#app/Modules/Tasks/Infrastructure/Repositories/TasksWriterSqliteRepository.ts";
import { TasksException } from "#app/Modules/Tasks/Domain/Exceptions/TasksException.ts";

export class DeleteTaskService {

    readonly #tasksReaderSqliteRepository: TasksReaderSqliteRepository;
    readonly #tasksWriterSqliteRepository: TasksWriterSqliteRepository;

    #deleteTaskDto!: DeleteTaskDto;

    constructor() {
        this.#tasksReaderSqliteRepository = TasksReaderSqliteRepository.getInstance();
        this.#tasksWriterSqliteRepository = TasksWriterSqliteRepository.getInstance();
    }

    static getInstance(): DeleteTaskService {
        return new DeleteTaskService();
    }

    /**
     * @throws TasksException
     */
    invoke(deleteTaskDto: DeleteTaskDto): DeleteTaskResultDto {
        this.#deleteTaskDto = deleteTaskDto;
        this.#failIfWrongInput();

        const taskEntity = this.#tryToGetOwnTaskOrFail();

        return DeleteTaskResultDto.fromPrimitives({
            taskUuid: taskEntity.getTaskUuid(),
            affected: this.#tasksWriterSqliteRepository.deleteTaskById(taskEntity),
        });
    }

    /**
     * @throws TasksException
     */
    #failIfWrongInput(): void {
        if (this.#deleteTaskDto.getAuthUserId() < 1) {
            TasksException.unauthorizedCustom(__(TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
    }

    /**
     * Una task de otro usuario responde igual que si no existiera (no se revela que existe).
     * @throws TasksException
     */
    #tryToGetOwnTaskOrFail(): TaskEntity {
        const taskUuid = this.#deleteTaskDto.getTaskUuid();
        const taskEntity = this.#tasksReaderSqliteRepository.getTaskByTaskUuid(taskUuid);
        if (!taskEntity || taskEntity.getUserId() !== this.#deleteTaskDto.getAuthUserId()) {
            TasksException.notFoundCustom(__(TasksMessageEnum.TASK_X_NOT_FOUND, taskUuid));
        }
        return taskEntity;
    }
}
