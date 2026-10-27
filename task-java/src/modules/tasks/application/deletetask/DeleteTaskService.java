package modules.tasks.application.deletetask;

import modules.shared.application.Primitives;
import modules.tasks.domain.entities.TaskEntity;
import modules.tasks.domain.enums.TasksMessageEnum;
import modules.tasks.domain.exceptions.TasksException;
import modules.tasks.infrastructure.repositories.TasksReaderSqliteRepository;
import modules.tasks.infrastructure.repositories.TasksWriterSqliteRepository;

import static core.config.Global.__;

public final class DeleteTaskService {

    private final TasksReaderSqliteRepository tasksReaderSqliteRepository;
    private final TasksWriterSqliteRepository tasksWriterSqliteRepository;

    private DeleteTaskDto deleteTaskDto;

    private DeleteTaskService() {
        this.tasksReaderSqliteRepository = TasksReaderSqliteRepository.getInstance();
        this.tasksWriterSqliteRepository = TasksWriterSqliteRepository.getInstance();
    }

    public static DeleteTaskService getInstance() {
        return new DeleteTaskService();
    }

    /**
     * @throws TasksException
     */
    public DeleteTaskResultDto invoke(DeleteTaskDto deleteTaskDto) {
        this.deleteTaskDto = deleteTaskDto;
        failIfWrongInput();

        TaskEntity taskEntity = tryToGetOwnTaskOrFail();

        return DeleteTaskResultDto.fromPrimitives(Primitives.of(
            "taskUuid", taskEntity.getTaskUuid(),
            "affected", tasksWriterSqliteRepository.deleteTaskById(taskEntity)
        ));
    }

    /**
     * @throws TasksException
     */
    private void failIfWrongInput() {
        if (deleteTaskDto.getAuthUserId() < 1) {
            throw TasksException.unauthorizedCustom(__(TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN));
        }
    }

    /**
     * Una task de otro usuario responde igual que si no existiera (no se revela que existe).
     * @throws TasksException
     */
    private TaskEntity tryToGetOwnTaskOrFail() {
        String taskUuid = deleteTaskDto.getTaskUuid();
        TaskEntity taskEntity = tasksReaderSqliteRepository.getTaskByTaskUuid(taskUuid);
        if (taskEntity == null || taskEntity.getUserId() == null || taskEntity.getUserId() != deleteTaskDto.getAuthUserId()) {
            throw TasksException.notFoundCustom(__(TasksMessageEnum.TASK_X_NOT_FOUND, taskUuid));
        }
        return taskEntity;
    }
}
