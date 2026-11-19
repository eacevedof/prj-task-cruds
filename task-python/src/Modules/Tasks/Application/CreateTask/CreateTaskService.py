from Core.config.global_ import __

from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Components.Date.DateTimer import DateTimer
from Modules.Shared.Infrastructure.Components.Uuid.UuidGenerator import UuidGenerator
from Modules.Shared.Infrastructure.Traits.ErrorStackTrait import ErrorStackTrait
from Modules.Shared.Infrastructure.Exceptions.FieldsException import FieldsException

from Modules.Tasks.Application.CreateTask.CreateTaskDto import CreateTaskDto
from Modules.Tasks.Application.CreateTask.CreateTaskResultDto import CreateTaskResultDto
from Modules.Tasks.Domain.Entities.TaskEntity import TaskEntity
from Modules.Tasks.Domain.Enums.TaskStatusEnum import TaskStatusEnum
from Modules.Tasks.Domain.Enums.TaskTypeEnum import TaskTypeEnum
from Modules.Tasks.Domain.Enums.TasksKeyEnum import TasksKeyEnum
from Modules.Tasks.Domain.Enums.TasksMessageEnum import TasksMessageEnum
from Modules.Tasks.Domain.Services.TaskFieldsValidator import TaskFieldsValidator
from Modules.Tasks.Infrastructure.Repositories.TasksReaderSqliteRepository import TasksReaderSqliteRepository
from Modules.Tasks.Infrastructure.Repositories.TasksWriterSqliteRepository import TasksWriterSqliteRepository
from Modules.Tasks.Domain.Exceptions.TasksException import TasksException


class CreateTaskService:
    """
    El dueño es siempre el usuario autenticado. task_status / task_type vacios -> su DEFAULT.
    """

    def __init__(self):
        self._errorStackTrait = ErrorStackTrait.getInstance()
        self._dateTimer = DateTimer.getInstance()
        self._tasksReaderSqliteRepository = TasksReaderSqliteRepository.getInstance()
        self._tasksWriterSqliteRepository = TasksWriterSqliteRepository.getInstance()

        self._createTaskDto: CreateTaskDto
        self._taskEntity: TaskEntity

    @staticmethod
    def getInstance() -> "CreateTaskService":
        return CreateTaskService()

    def invoke(self, createTaskDto: CreateTaskDto) -> CreateTaskResultDto:
        """
        @throws FieldsException
        @throws TasksException
        """
        self._createTaskDto = createTaskDto
        self._failIfWrongInput()

        self._taskEntity = TaskEntity.fromPrimitives({
            TasksKeyEnum.USER_ID: createTaskDto.getAuthUserId(),
            TasksKeyEnum.USER_UUID: createTaskDto.getAuthUserUuid(),
            TasksKeyEnum.TASK_UUID: UuidGenerator.getInstance().getUniqueIdWithPrefix(TaskEntity.TASK_UUID_PREFIX),
            TasksKeyEnum.TASK_TITLE: createTaskDto.getTaskTitle(),
            TasksKeyEnum.TASK_STATUS: createTaskDto.getTaskStatus() or TaskStatusEnum.DEFAULT,
            TasksKeyEnum.TASK_TYPE: createTaskDto.getTaskType() or TaskTypeEnum.DEFAULT,
            TasksKeyEnum.TARGET_DATE: createTaskDto.getTargetDate(),
            TasksKeyEnum.TASK_DESCRIPTION: createTaskDto.getTaskDescription(),
            TasksKeyEnum.CREATED_AT: self._dateTimer.getNowDtIntoTargetTz(),
        })

        self._failIfWrongFields()

        taskId = self._tasksWriterSqliteRepository.insertTask(self._taskEntity)

        taskEntity = self._tasksReaderSqliteRepository.getTaskById(taskId)
        return CreateTaskResultDto.fromPrimitives({
            "task": taskEntity.toPrimitives() if taskEntity is not None else {},
        })

    def _failIfWrongInput(self) -> None:
        """
        Por API nunca llega sin usuario (el controller corta con 401); protege otras entradas (consola, tests).
        @throws TasksException
        """
        if self._createTaskDto.getAuthUserId() < 1:
            TasksException.unauthorizedCustom(__(TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN))

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
