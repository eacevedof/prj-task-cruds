from Core.config.global_ import __

from Modules.Shared.Application.Primitives import getPrimitives
from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Controllers.AbstractHttpController import AbstractHttpController
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable
from Modules.Shared.Infrastructure.Exceptions.FieldsException import FieldsException

from Modules.GraphQl.Domain.Exceptions.GraphQlException import GraphQlException

from Modules.Tasks.Application.SearchTasks.SearchTasksDto import SearchTasksDto
from Modules.Tasks.Application.SearchTasks.SearchTasksService import SearchTasksService
from Modules.Tasks.Domain.Enums.TasksKeyEnum import TasksKeyEnum
from Modules.Tasks.Domain.Exceptions.TasksException import TasksException

from Modules.Users.Domain.Exceptions.UsersException import UsersException


class SearchTasksByUserGraphQlController(AbstractHttpController):
    """
    Campo User.tasks (src/Modules/Tasks/Infrastructure/Providers/tasks.graphql): las tasks de un usuario, paginadas y
    con filtros. Solo ejemplo, no lo consume ningun front. El uuid llega aparte porque sale del User padre, no del payload.
    Un usuario que no es el autenticado responde 404. El 401 de la autenticacion llega como UsersException.
    """

    @staticmethod
    def getInstance() -> "SearchTasksByUserGraphQlController":
        return SearchTasksByUserGraphQlController()

    def invoke(self, userUuid: str, gqlPayload: dict) -> dict:
        """
        gqlPayload: {filter?: {task_text?, task_status?, task_type?, target_date_from?, target_date_to?}, page?, page_size?}
        @throws GraphQlException
        """
        try:
            filter = getPrimitives(gqlPayload.get(TasksKeyEnum.FILTER))
            return SearchTasksService.getInstance().invoke(
                SearchTasksDto.fromPrimitives({
                    **self.httpRequest.getMetadata(),
                    **self.getAuthUserOrFail(),
                    "userUuid": userUuid,
                    "taskText": filter.get(TasksKeyEnum.TASK_TEXT) if filter.get(TasksKeyEnum.TASK_TEXT) is not None else "",
                    "taskStatus": filter.get(TasksKeyEnum.TASK_STATUS) if filter.get(TasksKeyEnum.TASK_STATUS) is not None else "",
                    "taskType": filter.get(TasksKeyEnum.TASK_TYPE) if filter.get(TasksKeyEnum.TASK_TYPE) is not None else "",
                    "targetDateFrom": filter.get(TasksKeyEnum.TARGET_DATE_FROM) if filter.get(TasksKeyEnum.TARGET_DATE_FROM) is not None else "",
                    "targetDateTo": filter.get(TasksKeyEnum.TARGET_DATE_TO) if filter.get(TasksKeyEnum.TARGET_DATE_TO) is not None else "",
                    "page": gqlPayload.get(TasksKeyEnum.PAGE),
                    "pageSize": gqlPayload.get(TasksKeyEnum.PAGE_SIZE),
                })
            ).toArray()
        except (FieldsException, UsersException, TasksException) as e:
            GraphQlException.clientAwareExceptionForWebonyx(e)
        except Exception as e:
            throwable = getThrowable(e)
            self.logTrait.logException(throwable, "SearchTasksByUserGraphQlController::invoke")
            self.mailTrait.sendMailOnThrowable(throwable, "SearchTasksByUserGraphQlController::invoke")
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
