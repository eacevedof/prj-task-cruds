package modules.tasks.application.createtask;

import java.util.List;

import modules.shared.application.Primitives;
import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.components.date.DateTimer;
import modules.shared.infrastructure.components.uuid.UuidGenerator;
import modules.shared.infrastructure.exceptions.FieldsException;
import modules.shared.infrastructure.traits.ErrorStackTrait;
import modules.tasks.domain.entities.TaskEntity;
import modules.tasks.domain.enums.TaskStatusEnum;
import modules.tasks.domain.enums.TaskTypeEnum;
import modules.tasks.domain.enums.TasksKeyEnum;
import modules.tasks.domain.enums.TasksMessageEnum;
import modules.tasks.domain.exceptions.TasksException;
import modules.tasks.domain.services.TaskFieldsValidator;
import modules.tasks.infrastructure.repositories.TasksReaderSqliteRepository;
import modules.tasks.infrastructure.repositories.TasksWriterSqliteRepository;

import static core.config.Global.__;

/**
 * El dueño es siempre el usuario autenticado. task_status / task_type vacios -> su DEFAULT.
 */
public final class CreateTaskService {

    private final ErrorStackTrait errorStackTrait;
    private final DateTimer dateTimer;
    private final TasksReaderSqliteRepository tasksReaderSqliteRepository;
    private final TasksWriterSqliteRepository tasksWriterSqliteRepository;

    private CreateTaskDto createTaskDto;
    private TaskEntity taskEntity;

    private CreateTaskService() {
        this.errorStackTrait = ErrorStackTrait.getInstance();
        this.dateTimer = DateTimer.getInstance();
        this.tasksReaderSqliteRepository = TasksReaderSqliteRepository.getInstance();
        this.tasksWriterSqliteRepository = TasksWriterSqliteRepository.getInstance();
    }

    public static CreateTaskService getInstance() {
        return new CreateTaskService();
    }

    /**
     * @throws FieldsException
     * @throws TasksException
     */
    public CreateTaskResultDto invoke(CreateTaskDto createTaskDto) {
        this.createTaskDto = createTaskDto;
        failIfWrongInput();

        String taskStatus = createTaskDto.getTaskStatus();
        String taskType = createTaskDto.getTaskType();
        taskEntity = TaskEntity.fromPrimitives(Primitives.of(
            TasksKeyEnum.USER_ID, createTaskDto.getAuthUserId(),
            TasksKeyEnum.USER_UUID, createTaskDto.getAuthUserUuid(),
            TasksKeyEnum.TASK_UUID, UuidGenerator.getInstance().getUniqueIdWithPrefix(TaskEntity.TASK_UUID_PREFIX),
            TasksKeyEnum.TASK_TITLE, createTaskDto.getTaskTitle(),
            TasksKeyEnum.TASK_STATUS, taskStatus.isEmpty() ? TaskStatusEnum.DEFAULT : taskStatus,
            TasksKeyEnum.TASK_TYPE, taskType.isEmpty() ? TaskTypeEnum.DEFAULT : taskType,
            TasksKeyEnum.TARGET_DATE, createTaskDto.getTargetDate(),
            TasksKeyEnum.TASK_DESCRIPTION, createTaskDto.getTaskDescription(),
            TasksKeyEnum.CREATED_AT, dateTimer.getNowDtIntoTargetTz()
        ));

        failIfWrongFields();

        int taskId = tasksWriterSqliteRepository.insertTask(taskEntity);

        TaskEntity createdTaskEntity = tasksReaderSqliteRepository.getTaskById(taskId);
        return CreateTaskResultDto.fromPrimitives(Primitives.of(
            "task", createdTaskEntity == null ? Primitives.of() : createdTaskEntity.toPrimitives()
        ));
    }

    /**
     * Por API nunca llega sin usuario (el controller corta con 401); protege otras entradas (consola, tests).
     * @throws TasksException
     */
    private void failIfWrongInput() {
        if (createTaskDto.getAuthUserId() < 1) {
            throw TasksException.unauthorizedCustom(__(TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
    }

    /**
     * @throws FieldsException
     */
    private void failIfWrongFields() {
        List<String> errors = TaskFieldsValidator.getInstance().getErrors(taskEntity);
        if (!errors.isEmpty()) {
            throw FieldsException.badRequestCustom(
                errorStackTrait.getErrorStackMessage(
                    __(SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors
                ));
        }
    }
}
