from Core.config.global_ import __

from Modules.Tasks.Application.GetTask.GetTaskDto import GetTaskDto
from Modules.Tasks.Application.GetTask.GetTaskResultDto import GetTaskResultDto
from Modules.Tasks.Domain.Entities.TaskEntity import TaskEntity
from Modules.Tasks.Domain.Enums.TasksMessageEnum import TasksMessageEnum
from Modules.Tasks.Infrastructure.Repositories.TasksReaderSqliteRepository import TasksReaderSqliteRepository
from Modules.Tasks.Domain.Exceptions.TasksException import TasksException


class GetTaskService:

    def __init__(self):
        self._tasksReaderSqliteRepository = TasksReaderSqliteRepository.getInstance()

        self._getTaskDto: GetTaskDto

    @staticmethod
    def getInstance() -> "GetTaskService":
        return GetTaskService()

    def invoke(self, getTaskDto: GetTaskDto) -> GetTaskResultDto:
        """
        @throws TasksException
        """
        self._getTaskDto = getTaskDto
        self._failIfWrongInput()

        return GetTaskResultDto.fromPrimitives({"task": self._tryToGetOwnTaskOrFail().toPrimitives()})

    def _failIfWrongInput(self) -> None:
        """
        @throws TasksException
        """
        if self._getTaskDto.getAuthUserId() < 1:
            TasksException.unauthorizedCustom(__(TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN))

    def _tryToGetOwnTaskOrFail(self) -> TaskEntity:
        """
        Una task de otro usuario responde igual que si no existiera (no se revela que existe).
        @throws TasksException
        """
        taskUuid = self._getTaskDto.getTaskUuid()
        taskEntity = self._tasksReaderSqliteRepository.getTaskByTaskUuid(taskUuid)
        if taskEntity is None or taskEntity.getUserId() != self._getTaskDto.getAuthUserId():
            TasksException.notFoundCustom(__(TasksMessageEnum.TASK_X_NOT_FOUND, taskUuid))
        return taskEntity
