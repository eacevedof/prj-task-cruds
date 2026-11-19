from Modules.Shared.Application.Primitives import getInt

from Modules.Tasks.Domain.Enums.TasksKeyEnum import TasksKeyEnum


class SearchTasksResultDto:

    def __init__(self, primitives: dict):
        # la pagina pedida: lista de TaskEntity.toPrimitives
        tasks = primitives.get("tasks")
        self._tasks = tasks if isinstance(tasks, list) else []
        self._page = getInt(primitives.get("page") if primitives.get("page") is not None else 0)
        self._pageSize = getInt(primitives.get("pageSize") if primitives.get("pageSize") is not None else 0)
        self._totalItems = getInt(primitives.get("totalItems") if primitives.get("totalItems") is not None else 0)
        self._totalPages = getInt(primitives.get("totalPages") if primitives.get("totalPages") is not None else 0)

    @staticmethod
    def fromPrimitives(primitives: dict) -> "SearchTasksResultDto":
        return SearchTasksResultDto(primitives)

    def getTasks(self) -> list[dict]:
        return self._tasks

    def getPage(self) -> int:
        return self._page

    def getPageSize(self) -> int:
        return self._pageSize

    def getTotalItems(self) -> int:
        return self._totalItems

    def getTotalPages(self) -> int:
        return self._totalPages

    def toArray(self) -> dict:
        """
        Tipo TaskPage de tasks.graphql.
        """
        return {
            TasksKeyEnum.ITEMS: self._tasks,
            TasksKeyEnum.PAGE: self._page,
            TasksKeyEnum.PAGE_SIZE: self._pageSize,
            TasksKeyEnum.TOTAL_ITEMS: self._totalItems,
            TasksKeyEnum.TOTAL_PAGES: self._totalPages,
        }
