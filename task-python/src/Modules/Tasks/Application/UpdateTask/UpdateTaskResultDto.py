from Modules.Shared.Application.Primitives import getPrimitives


class UpdateTaskResultDto:

    def __init__(self, primitives: dict):
        # la task modificada (TaskEntity.toPrimitives)
        self._task = getPrimitives(primitives.get("task"))

    @staticmethod
    def fromPrimitives(primitives: dict) -> "UpdateTaskResultDto":
        return UpdateTaskResultDto(primitives)

    def getTask(self) -> dict:
        return self._task

    def toArray(self) -> dict:
        return self._task
