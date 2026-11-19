from Modules.Shared.Application.AbstractInputDto import AbstractInputDto
from Modules.Shared.Application.Primitives import getInt, getTrimmedString


class CreateTaskDto(AbstractInputDto):

    def __init__(self, primitives: dict):
        super().__init__(primitives)

        self._authUserId = getInt(primitives.get("authUserId") if primitives.get("authUserId") is not None else 0)
        self._authUserUuid = getTrimmedString(primitives.get("authUserUuid"))
        self._taskTitle = getTrimmedString(primitives.get("taskTitle"))
        self._taskStatus = getTrimmedString(primitives.get("taskStatus"))
        self._taskType = getTrimmedString(primitives.get("taskType"))
        self._targetDate = getTrimmedString(primitives.get("targetDate"))
        self._taskDescription = getTrimmedString(primitives.get("taskDescription"))

    @staticmethod
    def fromPrimitives(primitives: dict) -> "CreateTaskDto":
        return CreateTaskDto(primitives)

    def getAuthUserId(self) -> int:
        return self._authUserId

    def getAuthUserUuid(self) -> str:
        return self._authUserUuid

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
