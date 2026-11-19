from Core.config.global_ import __

from Modules.Tasks.Application.DeleteTask.DeleteTaskDto import DeleteTaskDto
from Modules.Tasks.Application.DeleteTask.DeleteTaskResultDto import DeleteTaskResultDto
from Modules.Tasks.Domain.Entities.TaskEntity import TaskEntity
from Modules.Tasks.Domain.Enums.TasksMessageEnum import TasksMessageEnum
from Modules.Tasks.Infrastructure.Repositories.TasksReaderSqliteRepository import TasksReaderSqliteRepository
from Modules.Tasks.Infrastructure.Repositories.TasksWriterSqliteRepository import TasksWriterSqliteRepository
from Modules.Tasks.Domain.Exceptions.TasksException import TasksException


class DeleteTaskService:

    def __init__(self):
        self._tasksReaderSqliteRepository = TasksReaderSqliteRepository.getInstance()
        self._tasksWriterSqliteRepository = TasksWriterSqliteRepository.getInstance()

        self._deleteTaskDto: DeleteTaskDto

    @staticmethod
    def getInstance() -> "DeleteTaskService":
        return DeleteTaskService()

    def invoke(self, deleteTaskDto: DeleteTaskDto) -> DeleteTaskResultDto:
        """
        @throws TasksException
        """
        self._deleteTaskDto = deleteTaskDto
        self._failIfWrongInput()

        taskEntity = self._tryToGetOwnTaskOrFail()

        return DeleteTaskResultDto.fromPrimitives({
            "taskUuid": taskEntity.getTaskUuid(),
            "affected": self._tasksWriterSqliteRepository.deleteTaskById(taskEntity),
        })

    def _failIfWrongInput(self) -> None:
        """
        @throws TasksException
        """
        if self._deleteTaskDto.getAuthUserId() < 1:
            TasksException.unauthorizedCustom(__(TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN))

    def _tryToGetOwnTaskOrFail(self) -> TaskEntity:
        """
        Una task de otro usuario responde igual que si no existiera (no se revela que existe).
        @throws TasksException
        """
        taskUuid = self._deleteTaskDto.getTaskUuid()
        taskEntity = self._tasksReaderSqliteRepository.getTaskByTaskUuid(taskUuid)
        if taskEntity is None or taskEntity.getUserId() != self._deleteTaskDto.getAuthUserId():
            TasksException.notFoundCustom(__(TasksMessageEnum.TASK_X_NOT_FOUND, taskUuid))
        return taskEntity
