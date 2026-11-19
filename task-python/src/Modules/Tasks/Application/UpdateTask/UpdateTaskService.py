from Core.config.global_ import __

from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Components.Date.DateTimer import DateTimer
from Modules.Shared.Infrastructure.Traits.ErrorStackTrait import ErrorStackTrait
from Modules.Shared.Infrastructure.Exceptions.FieldsException import FieldsException

from Modules.Tasks.Application.UpdateTask.UpdateTaskDto import UpdateTaskDto
from Modules.Tasks.Application.UpdateTask.UpdateTaskResultDto import UpdateTaskResultDto
from Modules.Tasks.Domain.Entities.TaskEntity import TaskEntity
from Modules.Tasks.Domain.Enums.TaskStatusEnum import TaskStatusEnum
from Modules.Tasks.Domain.Enums.TaskTypeEnum import TaskTypeEnum
from Modules.Tasks.Domain.Enums.TasksKeyEnum import TasksKeyEnum
from Modules.Tasks.Domain.Enums.TasksMessageEnum import TasksMessageEnum
from Modules.Tasks.Domain.Services.TaskFieldsValidator import TaskFieldsValidator
from Modules.Tasks.Infrastructure.Repositories.TasksReaderSqliteRepository import TasksReaderSqliteRepository
from Modules.Tasks.Infrastructure.Repositories.TasksWriterSqliteRepository import TasksWriterSqliteRepository
from Modules.Tasks.Domain.Exceptions.TasksException import TasksException


class UpdateTaskService:
    """
    PUT: reemplazo completo (mismas reglas y defaults que el alta). El propietario no cambia.
    """

    def __init__(self):
        self._errorStackTrait = ErrorStackTrait.getInstance()
        self._dateTimer = DateTimer.getInstance()
        self._tasksReaderSqliteRepository = TasksReaderSqliteRepository.getInstance()
        self._tasksWriterSqliteRepository = TasksWriterSqliteRepository.getInstance()

        self._updateTaskDto: UpdateTaskDto
        self._taskEntity: TaskEntity

    @staticmethod
    def getInstance() -> "UpdateTaskService":
        return UpdateTaskService()

    def invoke(self, updateTaskDto: UpdateTaskDto) -> UpdateTaskResultDto:
        """
        @throws FieldsException
        @throws TasksException
        """
        self._updateTaskDto = updateTaskDto
        self._failIfWrongInput()

        currentTaskEntity = self._tryToGetOwnTaskOrFail()
        # reemplazo completo sobre la task actual: se conservan id, propietario y created_at
        self._taskEntity = TaskEntity.fromPrimitives({
            TasksKeyEnum.ID: currentTaskEntity.getId(),
            TasksKeyEnum.USER_ID: currentTaskEntity.getUserId(),
            TasksKeyEnum.USER_UUID: currentTaskEntity.getUserUuid(),
            TasksKeyEnum.TASK_UUID: currentTaskEntity.getTaskUuid(),
            TasksKeyEnum.TASK_TITLE: updateTaskDto.getTaskTitle(),
            TasksKeyEnum.TASK_STATUS: updateTaskDto.getTaskStatus() or TaskStatusEnum.DEFAULT,
            TasksKeyEnum.TASK_TYPE: updateTaskDto.getTaskType() or TaskTypeEnum.DEFAULT,
            TasksKeyEnum.TARGET_DATE: updateTaskDto.getTargetDate(),
            TasksKeyEnum.TASK_DESCRIPTION: updateTaskDto.getTaskDescription(),
            TasksKeyEnum.CREATED_AT: currentTaskEntity.getCreatedAt(),
            TasksKeyEnum.UPDATED_AT: self._dateTimer.getNowDtIntoTargetTz(),
        })

        self._failIfWrongFields()

        self._tasksWriterSqliteRepository.updateTaskById(self._taskEntity)

        taskEntity = self._tasksReaderSqliteRepository.getTaskById(self._taskEntity.getId() or 0)
        return UpdateTaskResultDto.fromPrimitives({
            "task": taskEntity.toPrimitives() if taskEntity is not None else {},
        })

    def _failIfWrongInput(self) -> None:
        """
        @throws TasksException
        """
        if self._updateTaskDto.getAuthUserId() < 1:
            TasksException.unauthorizedCustom(__(TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN))

    def _tryToGetOwnTaskOrFail(self) -> TaskEntity:
        """
        Una task de otro usuario responde igual que si no existiera (no se revela que existe).
        @throws TasksException
        """
        taskUuid = self._updateTaskDto.getTaskUuid()
        taskEntity = self._tasksReaderSqliteRepository.getTaskByTaskUuid(taskUuid)
        if taskEntity is None or taskEntity.getUserId() != self._updateTaskDto.getAuthUserId():
            TasksException.notFoundCustom(__(TasksMessageEnum.TASK_X_NOT_FOUND, taskUuid))
        return taskEntity

    def _failIfWrongFields(self) -> None:
        """
        @throws FieldsException
        """
        errors = TaskFieldsValidator.getInstance().getErrors(self._taskEntity)
        if errors:
            FieldsException.badRequestCustom(
                self._errorStackTrait.getErrorStackMessage(
                    __(SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors
                ))
