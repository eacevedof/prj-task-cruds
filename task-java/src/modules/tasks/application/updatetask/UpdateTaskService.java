package modules.tasks.application.updatetask;

import java.util.List;

import modules.shared.application.Primitives;
import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.components.date.DateTimer;
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
 * PUT: reemplazo completo (mismas reglas y defaults que el alta). El propietario no cambia.
 */
public final class UpdateTaskService {

    private final ErrorStackTrait errorStackTrait;
    private final DateTimer dateTimer;
    private final TasksReaderSqliteRepository tasksReaderSqliteRepository;
    private final TasksWriterSqliteRepository tasksWriterSqliteRepository;

    private UpdateTaskDto updateTaskDto;
    private TaskEntity taskEntity;

    private UpdateTaskService() {
        this.errorStackTrait = ErrorStackTrait.getInstance();
        this.dateTimer = DateTimer.getInstance();
        this.tasksReaderSqliteRepository = TasksReaderSqliteRepository.getInstance();
        this.tasksWriterSqliteRepository = TasksWriterSqliteRepository.getInstance();
    }

    public static UpdateTaskService getInstance() {
        return new UpdateTaskService();
    }

    /**
     * @throws FieldsException
     * @throws TasksException
     */
    public UpdateTaskResultDto invoke(UpdateTaskDto updateTaskDto) {
        this.updateTaskDto = updateTaskDto;
        failIfWrongInput();

        TaskEntity currentTaskEntity = tryToGetOwnTaskOrFail();
        // reemplazo completo sobre la task actual: se conservan id, propietario y created_at
        String taskStatus = updateTaskDto.getTaskStatus();
        String taskType = updateTaskDto.getTaskType();
        taskEntity = TaskEntity.fromPrimitives(Primitives.of(
            TasksKeyEnum.ID, currentTaskEntity.getId(),
            TasksKeyEnum.USER_ID, currentTaskEntity.getUserId(),
            TasksKeyEnum.USER_UUID, currentTaskEntity.getUserUuid(),
            TasksKeyEnum.TASK_UUID, currentTaskEntity.getTaskUuid(),
            TasksKeyEnum.TASK_TITLE, updateTaskDto.getTaskTitle(),
            TasksKeyEnum.TASK_STATUS, taskStatus.isEmpty() ? TaskStatusEnum.DEFAULT : taskStatus,
            TasksKeyEnum.TASK_TYPE, taskType.isEmpty() ? TaskTypeEnum.DEFAULT : taskType,
            TasksKeyEnum.TARGET_DATE, updateTaskDto.getTargetDate(),
            TasksKeyEnum.TASK_DESCRIPTION, updateTaskDto.getTaskDescription(),
            TasksKeyEnum.CREATED_AT, currentTaskEntity.getCreatedAt(),
            TasksKeyEnum.UPDATED_AT, dateTimer.getNowDtIntoTargetTz()
        ));

        failIfWrongFields();

        tasksWriterSqliteRepository.updateTaskById(taskEntity);

        TaskEntity updatedTaskEntity = tasksReaderSqliteRepository.getTaskById(taskEntity.getId() == null ? 0 : taskEntity.getId());
        return UpdateTaskResultDto.fromPrimitives(Primitives.of(
            "task", updatedTaskEntity == null ? Primitives.of() : updatedTaskEntity.toPrimitives()
        ));
    }

    /**
     * @throws TasksException
     */
    private void failIfWrongInput() {
        if (updateTaskDto.getAuthUserId() < 1) {
            throw TasksException.unauthorizedCustom(__(TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
    }

    /**
     * Una task de otro usuario responde igual que si no existiera (no se revela que existe).
     * @throws TasksException
     */
    private TaskEntity tryToGetOwnTaskOrFail() {
        String taskUuid = updateTaskDto.getTaskUuid();
        TaskEntity currentTaskEntity = tasksReaderSqliteRepository.getTaskByTaskUuid(taskUuid);
        if (currentTaskEntity == null || currentTaskEntity.getUserId() == null
            || currentTaskEntity.getUserId() != updateTaskDto.getAuthUserId()) {
            throw TasksException.notFoundCustom(__(TasksMessageEnum.TASK_X_NOT_FOUND, taskUuid));
        }
        return currentTaskEntity;
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
