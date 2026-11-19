from Modules.Shared.Application.Primitives import getInt, getTrimmedString

from Modules.Tasks.Domain.Enums.TasksKeyEnum import TasksKeyEnum


class DeleteTaskResultDto:

    def __init__(self, primitives: dict):
        self._taskUuid = getTrimmedString(primitives.get("taskUuid"))
        self._affected = getInt(primitives.get("affected") if primitives.get("affected") is not None else 0)

    @staticmethod
    def fromPrimitives(primitives: dict) -> "DeleteTaskResultDto":
        return DeleteTaskResultDto(primitives)

    def getTaskUuid(self) -> str:
        return self._taskUuid

    def getAffected(self) -> int:
        return self._affected

    def toArray(self) -> dict:
        return {
            TasksKeyEnum.TASK_UUID: self._taskUuid,
            TasksKeyEnum.AFFECTED: self._affected,
        }
