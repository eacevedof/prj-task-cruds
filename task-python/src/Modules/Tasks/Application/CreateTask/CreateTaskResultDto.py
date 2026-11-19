from Modules.Shared.Application.Primitives import getPrimitives


class CreateTaskResultDto:

    def __init__(self, primitives: dict):
        # la task creada (TaskEntity.toPrimitives)
        self._task = getPrimitives(primitives.get("task"))

    @staticmethod
    def fromPrimitives(primitives: dict) -> "CreateTaskResultDto":
        return CreateTaskResultDto(primitives)

    def getTask(self) -> dict:
        return self._task

    def toArray(self) -> dict:
        return self._task
