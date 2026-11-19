from Modules.Shared.Application.AbstractInputDto import AbstractInputDto
from Modules.Shared.Application.Primitives import getInt, getTrimmedString


class GetTaskDto(AbstractInputDto):

    def __init__(self, primitives: dict):
        super().__init__(primitives)

        self._authUserId = getInt(primitives.get("authUserId") if primitives.get("authUserId") is not None else 0)
        self._authUserUuid = getTrimmedString(primitives.get("authUserUuid"))
        self._taskUuid = getTrimmedString(primitives.get("taskUuid"))

    @staticmethod
    def fromPrimitives(primitives: dict) -> "GetTaskDto":
        return GetTaskDto(primitives)

    def getAuthUserId(self) -> int:
        return self._authUserId

    def getAuthUserUuid(self) -> str:
        return self._authUserUuid

    def getTaskUuid(self) -> str:
        return self._taskUuid
