import math

from Core.config.global_ import __

from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Traits.ErrorStackTrait import ErrorStackTrait
from Modules.Shared.Infrastructure.Exceptions.FieldsException import FieldsException

from Modules.Tasks.Application.SearchTasks.SearchTasksDto import SearchTasksDto
from Modules.Tasks.Application.SearchTasks.SearchTasksResultDto import SearchTasksResultDto
from Modules.Tasks.Domain.Enums.TasksMessageEnum import TasksMessageEnum
from Modules.Tasks.Domain.Services.TasksSearchValidator import TasksSearchValidator
from Modules.Tasks.Infrastructure.Repositories.TasksReaderSqliteRepository import TasksReaderSqliteRepository
from Modules.Tasks.Domain.Exceptions.TasksException import TasksException


class SearchTasksService:
    """
    Busqueda paginada de las tasks del usuario autenticado (mas reciente primero): filtros por texto, estado,
    tipo y rango de target_date.
    """

    def __init__(self):
        self._errorStackTrait = ErrorStackTrait.getInstance()
        self._tasksSearchValidator = TasksSearchValidator.getInstance()
        self._tasksReaderSqliteRepository = TasksReaderSqliteRepository.getInstance()

        self._searchTasksDto: SearchTasksDto

    @staticmethod
    def getInstance() -> "SearchTasksService":
        return SearchTasksService()

    def invoke(self, searchTasksDto: SearchTasksDto) -> SearchTasksResultDto:
        """
        @throws FieldsException
        @throws TasksException
        """
        self._searchTasksDto = searchTasksDto
        self._failIfWrongInput()
        self._failIfWrongSearch()

        userId = self._searchTasksDto.getAuthUserId()

        totalItems = self._tasksReaderSqliteRepository.getTasksCountByUserIdAndFilters(
            userId,
            self._searchTasksDto.getTaskText(),
            self._searchTasksDto.getTaskStatus(),
            self._searchTasksDto.getTaskType(),
            self._searchTasksDto.getTargetDateFrom(),
            self._searchTasksDto.getTargetDateTo()
        )
        page = self._searchTasksDto.getPage()
        pageSize = self._searchTasksDto.getPageSize()
        taskEntities = self._tasksReaderSqliteRepository.getTasksByUserIdAndFilters(
            userId,
            self._searchTasksDto.getTaskText(),
            self._searchTasksDto.getTaskStatus(),
            self._searchTasksDto.getTaskType(),
            self._searchTasksDto.getTargetDateFrom(),
            self._searchTasksDto.getTargetDateTo(),
            pageSize,
            (page - 1) * pageSize
        )

        return SearchTasksResultDto.fromPrimitives({
            "tasks": [taskEntity.toPrimitives() for taskEntity in taskEntities],
            "page": page,
            "pageSize": pageSize,
            "totalItems": totalItems,
            "totalPages": math.ceil(totalItems / pageSize),
        })

    def _failIfWrongInput(self) -> None:
        """
        Sin user_uuid se busca en las del autenticado; con uno ajeno responde igual que si no existiera.
        @throws TasksException
        """
        if self._searchTasksDto.getAuthUserId() < 1:
            TasksException.unauthorizedCustom(__(TasksMessageEnum.MISSING_OR_INVALID_API_TOKEN))
        userUuid = self._searchTasksDto.getUserUuid() or self._searchTasksDto.getAuthUserUuid()
        if userUuid != self._searchTasksDto.getAuthUserUuid():
            TasksException.notFoundCustom(__(TasksMessageEnum.USER_X_NOT_FOUND, userUuid))

    def _failIfWrongSearch(self) -> None:
        """
        @throws FieldsException
        """
        errors = self._tasksSearchValidator.getErrors(
            self._searchTasksDto.getTaskStatus(),
            self._searchTasksDto.getTaskType(),
            self._searchTasksDto.getTargetDateFrom(),
            self._searchTasksDto.getTargetDateTo(),
            self._searchTasksDto.getPage(),
            self._searchTasksDto.getPageSize()
        )
        if not errors:
            return

        FieldsException.badRequestCustom(
            self._errorStackTrait.getErrorStackMessage(
                __(SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors
            ))
