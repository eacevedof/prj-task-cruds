import { __ } from "#app/Core/config/global.ts";

import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import { DateTimer } from "#app/Modules/Shared/Infrastructure/Components/Date/DateTimer.ts";
import { UuidGenerator } from "#app/Modules/Shared/Infrastructure/Components/Uuid/UuidGenerator.ts";
import { ErrorStackTrait } from "#app/Modules/Shared/Infrastructure/Traits/ErrorStackTrait.ts";
import { FieldsException } from "#app/Modules/Shared/Infrastructure/Exceptions/FieldsException.ts";

import type { CreateTaskDto } from "#app/Modules/Tasks/Application/CreateTask/CreateTaskDto.ts";
import { CreateTaskResultDto } from "#app/Modules/Tasks/Application/CreateTask/CreateTaskResultDto.ts";
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
 * El dueño es siempre el usuario autenticado. task_status / task_type vacios -> su DEFAULT.
 */
export class CreateTaskService {

    readonly #errorStackTrait: ErrorStackTrait;
    readonly #dateTimer: DateTimer;
    readonly #tasksReaderSqliteRepository: TasksReaderSqliteRepository;
    readonly #tasksWriterSqliteRepository: TasksWriterSqliteRepository;

    #createTaskDto!: CreateTaskDto;
    #taskEntity!: TaskEntity;

    constructor() {
        this.#errorStackTrait = ErrorStackTrait.getInstance();
        this.#dateTimer = DateTimer.getInstance();
        this.#tasksReaderSqliteRepository = TasksReaderSqliteRepository.getInstance();
        this.#tasksWriterSqliteRepository = TasksWriterSqliteRepository.getInstance();
    }

    static getInstance(): CreateTaskService {
        return new CreateTaskService();
    }

    /**
     * @throws FieldsException
     * @throws TasksException
     */
    invoke(createTaskDto: CreateTaskDto): CreateTaskResultDto {
        this.#createTaskDto = createTaskDto;
        this.#failIfWrongInput();

        this.#taskEntity = TaskEntity.fromPrimitives({
            [TasksKeyEnum.USER_ID]: createTaskDto.getAuthUserId(),
            [TasksKeyEnum.USER_UUID]: createTaskDto.getAuthUserUuid(),
            [TasksKeyEnum.TASK_UUID]: UuidGenerator.getInstance().getUniqueIdWithPrefix(TaskEntity.TASK_UUID_PREFIX),
            [TasksKeyEnum.TASK_TITLE]: createTaskDto.getTaskTitle(),
            [TasksKeyEnum.TASK_STATUS]: createTaskDto.getTaskStatus() || TaskStatusEnum.DEFAULT,
            [TasksKeyEnum.TASK_TYPE]: createTaskDto.getTaskType() || TaskTypeEnum.DEFAULT,
            [TasksKeyEnum.TARGET_DATE]: createTaskDto.getTargetDate(),
            [TasksKeyEnum.TASK_DESCRIPTION]: createTaskDto.getTaskDescription(),
            [TasksKeyEnum.CREATED_AT]: this.#dateTimer.getNowDtIntoTargetTz(),
        });

        this.#failIfWrongFields();

        const taskId = this.#tasksWriterSqliteRepository.insertTask(this.#taskEntity);

        return CreateTaskResultDto.fromPrimitives({
            task: this.#tasksReaderSqliteRepository.getTaskById(taskId)?.toPrimitives() ?? {},
        });
    }

    /**
     * Por API nunca llega sin usuario (el controller corta con 401); protege otras entradas (consola, tests).
     * @throws TasksException
     */
    #failIfWrongInput(): void {
        if (this.#createTaskDto.getAuthUserId() < 1) {
            TasksException.unauthorizedCustom(__(TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
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
