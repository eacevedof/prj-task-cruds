from Modules.Shared.Application.Primitives import getInt, getTrimmedString

from Modules.Tasks.Domain.Enums.TasksKeyEnum import TasksKeyEnum


class TaskEntity:

    TASK_UUID_PREFIX = "task-"

    def __init__(self, primitives: dict):
        """
        Desde una fila de tasks JOIN users (user_uuid viene del usuario propietario; columnas en snake_case:
        contrato con la BD). target_date / task_description NULL en BD -> "" aqui (neutro de string).
        """
        self._id = getInt(primitives.get(TasksKeyEnum.ID)) if primitives.get(TasksKeyEnum.ID) is not None else None
        self._userId = getInt(primitives.get(TasksKeyEnum.USER_ID)) if primitives.get(TasksKeyEnum.USER_ID) is not None else None
        self._userUuid = getTrimmedString(primitives.get(TasksKeyEnum.USER_UUID))
        self._taskUuid = getTrimmedString(primitives.get(TasksKeyEnum.TASK_UUID))
        self._taskTitle = getTrimmedString(primitives.get(TasksKeyEnum.TASK_TITLE))
        self._taskStatus = getTrimmedString(primitives.get(TasksKeyEnum.TASK_STATUS))
        self._taskType = getTrimmedString(primitives.get(TasksKeyEnum.TASK_TYPE))
        self._targetDate = getTrimmedString(primitives.get(TasksKeyEnum.TARGET_DATE))
        self._taskDescription = getTrimmedString(primitives.get(TasksKeyEnum.TASK_DESCRIPTION))
        self._createdAt = getTrimmedString(primitives.get(TasksKeyEnum.CREATED_AT))
        self._updatedAt = getTrimmedString(primitives.get(TasksKeyEnum.UPDATED_AT))

    @staticmethod
    def fromPrimitives(primitives: dict) -> "TaskEntity":
        return TaskEntity(primitives)

    def toPrimitives(self) -> dict:
        """
        Representacion publica (API): el identificador para operar es task_uuid; task_id es solo informativo
        (orden, referencia legible). El propietario va por user_uuid, nunca por su id interno.
        Los opcionales vacios salen como null (contrato de la API).
        """
        return {
            TasksKeyEnum.TASK_ID: self._id,
            TasksKeyEnum.TASK_UUID: self._taskUuid,
            TasksKeyEnum.USER_UUID: self._userUuid,
            TasksKeyEnum.TASK_TITLE: self._taskTitle,
            TasksKeyEnum.TASK_STATUS: self._taskStatus,
            TasksKeyEnum.TASK_TYPE: self._taskType,
            TasksKeyEnum.TARGET_DATE: self._targetDate or None,
            TasksKeyEnum.TASK_DESCRIPTION: self._taskDescription or None,
            TasksKeyEnum.CREATED_AT: self._createdAt,
            TasksKeyEnum.UPDATED_AT: self._updatedAt,
        }

    def getId(self) -> int | None:
        return self._id

    def getUserId(self) -> int | None:
        return self._userId

    def getUserUuid(self) -> str:
        return self._userUuid

    def getTaskUuid(self) -> str:
        return self._taskUuid

    def getTaskTitle(self) -> str:
        return self._taskTitle

    def getTaskStatus(self) -> str:
        return self._taskStatus

    def getTaskType(self) -> str:
        return self._taskType

    def getTargetDate(self) -> str:
        return self._targetDate

    def getTaskDescription(self) -> str:
        return self._taskDescription

    def getCreatedAt(self) -> str:
        return self._createdAt

    def getUpdatedAt(self) -> str:
        return self._updatedAt
