import { __ } from "#app/Core/config/global.ts";

import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { DateTimer } from "#app/Modules/Shared/Infrastructure/Components/Date/DateTimer.ts";
import { ErrorStackTrait } from "#app/Modules/Shared/Infrastructure/Traits/ErrorStackTrait.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import type { UpdateTaskDto } from "#app/Modules/Tasks/Application/UpdateTask/UpdateTaskDto.ts";
import { UpdateTaskResultDto } from "#app/Modules/Tasks/Application/UpdateTask/UpdateTaskResultDto.ts";
import { TaskEntity } from "#app/Modules/Tasks/Domain/Entities/TaskEntity.ts";
import { TaskStatusEnum } from "#app/Modules/Tasks/Domain/Enums/TaskStatusEnum.ts";
import { TaskTypeEnum } from "#app/Modules/Tasks/Domain/Enums/TaskTypeEnum.ts";
import { TasksKeyEnum } from "#app/Modules/Tasks/Domain/Enums/TasksKeyEnum.ts";
import { TasksMessageEnum } from "#app/Modules/Tasks/Domain/Enums/TasksMessageEnum.ts";
import { TaskFieldsValidator } from "#app/Modules/Tasks/Domain/Services/TaskFieldsValidator.ts";
import { TasksReaderSqliteRepository } from "#app/Modules/Tasks/Infrastructure/Repositories/TasksReaderSqliteRepository.ts";
import { TasksWriterSqliteRepository } from "#app/Modules/Tasks/Infrastructure/Repositories/TasksWriterSqliteRepository.ts";
import { TasksException } from "#app/Modules/Tasks/Domain/Exceptions/TasksException.ts";

/**
 * PUT: reemplazo completo (mismas reglas y defaults que el alta). El propietario no cambia.
 */
export class UpdateTaskService {

    readonly #errorStackTrait: ErrorStackTrait;
    readonly #dateTimer: DateTimer;
    readonly #tasksReaderSqliteRepository: TasksReaderSqliteRepository;
    readonly #tasksWriterSqliteRepository: TasksWriterSqliteRepository;

    #updateTaskDto!: UpdateTaskDto;
    #taskEntity!: TaskEntity;

    constructor() {
        this.#errorStackTrait = ErrorStackTrait.getInstance();
        this.#dateTimer = DateTimer.getInstance();
        this.#tasksReaderSqliteRepository = TasksReaderSqliteRepository.getInstance();
        this.#tasksWriterSqliteRepository = TasksWriterSqliteRepository.getInstance();
    }

    static getInstance(): UpdateTaskService {
        return new UpdateTaskService();
    }

    /**
     * @throws FieldsException
     * @throws TasksException
     */
    invoke(updateTaskDto: UpdateTaskDto): UpdateTaskResultDto {
        this.#updateTaskDto = updateTaskDto;
        this.#failIfWrongInput();

        const currentTaskEntity = this.#tryToGetOwnTaskOrFail();
        // reemplazo completo sobre la task actual: se conservan id, propietario y created_at
        this.#taskEntity = TaskEntity.fromPrimitives({
            [TasksKeyEnum.ID]: currentTaskEntity.getId(),
            [TasksKeyEnum.USER_ID]: currentTaskEntity.getUserId(),
            [TasksKeyEnum.USER_UUID]: currentTaskEntity.getUserUuid(),
            [TasksKeyEnum.TASK_UUID]: currentTaskEntity.getTaskUuid(),
            [TasksKeyEnum.TASK_TITLE]: updateTaskDto.getTaskTitle(),
            [TasksKeyEnum.TASK_STATUS]: updateTaskDto.getTaskStatus() || TaskStatusEnum.DEFAULT,
            [TasksKeyEnum.TASK_TYPE]: updateTaskDto.getTaskType() || TaskTypeEnum.DEFAULT,
            [TasksKeyEnum.TARGET_DATE]: updateTaskDto.getTargetDate(),
            [TasksKeyEnum.TASK_DESCRIPTION]: updateTaskDto.getTaskDescription(),
            [TasksKeyEnum.CREATED_AT]: currentTaskEntity.getCreatedAt(),
            [TasksKeyEnum.UPDATED_AT]: this.#dateTimer.getNowDtIntoTargetTz(),
        });

        this.#failIfWrongFields();

        this.#tasksWriterSqliteRepository.updateTaskById(this.#taskEntity);

        return UpdateTaskResultDto.fromPrimitives({
            task: this.#tasksReaderSqliteRepository.getTaskById(this.#taskEntity.getId() ?? 0)?.toPrimitives() ?? {},
        });
    }

    /**
     * @throws TasksException
     */
    #failIfWrongInput(): void {
        if (this.#updateTaskDto.getAuthUserId() < 1) {
            TasksException.unauthorizedCustom(__(TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
    }

    /**
     * Una task de otro usuario responde igual que si no existiera (no se revela que existe).
     * @throws TasksException
     */
    #tryToGetOwnTaskOrFail(): TaskEntity {
        const taskUuid = this.#updateTaskDto.getTaskUuid();
        const taskEntity = this.#tasksReaderSqliteRepository.getTaskByTaskUuid(taskUuid);
        if (!taskEntity || taskEntity.getUserId() !== this.#updateTaskDto.getAuthUserId()) {
            TasksException.notFoundCustom(__(TasksMessageEnum.TASK_X_NOT_FOUND, taskUuid));
        }
        return taskEntity;
    }

    /**
     * @throws FieldsException
     */
    #failIfWrongFields(): void {
        const errors = TaskFieldsValidator.getInstance().getErrors(this.#taskEntity);
        if (errors.length) {
            FieldsException.badRequestCustom(
                this.#errorStackTrait.getErrorStackMessage(
                    __(SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors
                ));
        }
    }
}
