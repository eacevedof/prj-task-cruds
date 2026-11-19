from Core.config.global_ import __

from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Controllers.AbstractHttpController import AbstractHttpController
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable
from Modules.Shared.Infrastructure.Exceptions.FieldsException import FieldsException

from Modules.GraphQl.Domain.Exceptions.GraphQlException import GraphQlException

from Modules.Tasks.Application.DeleteTask.DeleteTaskDto import DeleteTaskDto
from Modules.Tasks.Application.DeleteTask.DeleteTaskService import DeleteTaskService
from Modules.Tasks.Domain.Enums.TasksKeyEnum import TasksKeyEnum
from Modules.Tasks.Domain.Exceptions.TasksException import TasksException

from Modules.Users.Domain.Exceptions.UsersException import UsersException


class DeleteTaskGraphQlController(AbstractHttpController):
    """
    Mutation deleteTask (src/Modules/Tasks/Infrastructure/Providers/tasks.graphql). El 401 de la autenticacion llega como UsersException.
    """

    @staticmethod
    def getInstance() -> "DeleteTaskGraphQlController":
        return DeleteTaskGraphQlController()

    def invoke(self, gqlPayload: dict) -> dict:
        """
        @throws GraphQlException
        """
        try:
            return DeleteTaskService.getInstance().invoke(
                DeleteTaskDto.fromPrimitives({
                    **self.httpRequest.getMetadata(),
                    **self.getAuthUserOrFail(),
                    "taskUuid": gqlPayload.get(TasksKeyEnum.TASK_UUID) if gqlPayload.get(TasksKeyEnum.TASK_UUID) is not None else "",
                })
            ).toArray()
        except (FieldsException, UsersException, TasksException) as e:
            GraphQlException.clientAwareExceptionForWebonyx(e)
        except Exception as e:
            throwable = getThrowable(e)
            self.logTrait.logException(throwable, "DeleteTaskGraphQlController::invoke")
            self.mailTrait.sendMailOnThrowable(throwable, "DeleteTaskGraphQlController::invoke")
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
