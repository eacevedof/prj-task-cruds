from Modules.Shared.Application.Primitives import getPrimitives


class GetTaskResultDto:

    def __init__(self, primitives: dict):
        # la task (TaskEntity.toPrimitives)
        self._task = getPrimitives(primitives.get("task"))

    @staticmethod
    def fromPrimitives(primitives: dict) -> "GetTaskResultDto":
        return GetTaskResultDto(primitives)

    def getTask(self) -> dict:
        return self._task

    def toArray(self) -> dict:
        return self._task
