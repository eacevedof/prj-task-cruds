package modules.tasks.application.gettask;

import modules.shared.application.Primitives;
import modules.tasks.domain.entities.TaskEntity;
import modules.tasks.domain.enums.TasksMessageEnum;
import modules.tasks.domain.exceptions.TasksException;
import modules.tasks.infrastructure.repositories.TasksReaderSqliteRepository;

import static core.config.Global.__;

public final class GetTaskService {

    private final TasksReaderSqliteRepository tasksReaderSqliteRepository;

    private GetTaskDto getTaskDto;

    private GetTaskService() {
        this.tasksReaderSqliteRepository = TasksReaderSqliteRepository.getInstance();
    }

    public static GetTaskService getInstance() {
        return new GetTaskService();
    }

    /**
     * @throws TasksException
     */
    public GetTaskResultDto invoke(GetTaskDto getTaskDto) {
        this.getTaskDto = getTaskDto;
        failIfWrongInput();

        return GetTaskResultDto.fromPrimitives(Primitives.of("task", tryToGetOwnTaskOrFail().toPrimitives()));
    }

    /**
     * @throws TasksException
     */
    private void failIfWrongInput() {
        if (getTaskDto.getAuthUserId() < 1) {
            throw TasksException.unauthorizedCustom(__(TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
    }

    /**
     * Una task de otro usuario responde igual que si no existiera (no se revela que existe).
     * @throws TasksException
     */
    private TaskEntity tryToGetOwnTaskOrFail() {
        String taskUuid = getTaskDto.getTaskUuid();
        TaskEntity taskEntity = tasksReaderSqliteRepository.getTaskByTaskUuid(taskUuid);
        if (taskEntity == null || taskEntity.getUserId() == null || taskEntity.getUserId() != getTaskDto.getAuthUserId()) {
            throw TasksException.notFoundCustom(__(TasksMessageEnum.TASK_X_NOT_FOUND, taskUuid));
        }
        return taskEntity;
    }
}
