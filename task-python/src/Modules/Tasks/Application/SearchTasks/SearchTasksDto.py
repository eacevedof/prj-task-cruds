from Modules.Shared.Application.AbstractInputDto import AbstractInputDto
from Modules.Shared.Application.Primitives import getInt, getTrimmedString

from Modules.Tasks.Domain.Services.TasksSearchValidator import TasksSearchValidator


class SearchTasksDto(AbstractInputDto):

    """
    userUuid vacio = las del usuario autenticado. Sin page/pageSize: primera pagina con el tamaño por defecto.
    """

    def __init__(self, primitives: dict):
        super().__init__(primitives)

        self._authUserId = getInt(primitives.get("authUserId") if primitives.get("authUserId") is not None else 0)
        self._authUserUuid = getTrimmedString(primitives.get("authUserUuid"))
        self._userUuid = getTrimmedString(primitives.get("userUuid"))
        self._taskText = getTrimmedString(primitives.get("taskText"))
        self._taskStatus = getTrimmedString(primitives.get("taskStatus"))
        self._taskType = getTrimmedString(primitives.get("taskType"))
        self._targetDateFrom = getTrimmedString(primitives.get("targetDateFrom"))
        self._targetDateTo = getTrimmedString(primitives.get("targetDateTo"))
        self._page = getInt(primitives.get("page") if primitives.get("page") is not None else TasksSearchValidator.PAGE_MIN)
        self._pageSize = getInt(primitives.get("pageSize") if primitives.get("pageSize") is not None else TasksSearchValidator.PAGE_SIZE_DEFAULT)

    @staticmethod
    def fromPrimitives(primitives: dict) -> "SearchTasksDto":
        return SearchTasksDto(primitives)

    def getAuthUserId(self) -> int:
        return self._authUserId

    def getAuthUserUuid(self) -> str:
        return self._authUserUuid

    def getUserUuid(self) -> str:
        return self._userUuid

    def getTaskText(self) -> str:
        return self._taskText

    def getTaskStatus(self) -> str:
        return self._taskStatus

    def getTaskType(self) -> str:
        return self._taskType

    def getTargetDateFrom(self) -> str:
        return self._targetDateFrom

    def getTargetDateTo(self) -> str:
        return self._targetDateTo

    def getPage(self) -> int:
        return self._page

    def getPageSize(self) -> int:
        return self._pageSize
