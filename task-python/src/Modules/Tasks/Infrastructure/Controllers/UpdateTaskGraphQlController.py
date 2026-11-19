from Core.config.global_ import __

from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Controllers.AbstractHttpController import AbstractHttpController
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable
from Modules.Shared.Infrastructure.Exceptions.FieldsException import FieldsException

from Modules.GraphQl.Domain.Exceptions.GraphQlException import GraphQlException

from Modules.Tasks.Application.UpdateTask.UpdateTaskDto import UpdateTaskDto
from Modules.Tasks.Application.UpdateTask.UpdateTaskService import UpdateTaskService
from Modules.Tasks.Domain.Enums.TasksKeyEnum import TasksKeyEnum
from Modules.Tasks.Domain.Exceptions.TasksException import TasksException

from Modules.Users.Domain.Exceptions.UsersException import UsersException


class UpdateTaskGraphQlController(AbstractHttpController):
    """
    Mutation updateTask (src/Modules/Tasks/Infrastructure/Providers/tasks.graphql). El 401 de la autenticacion llega como UsersException.
    """

    @staticmethod
    def getInstance() -> "UpdateTaskGraphQlController":
        return UpdateTaskGraphQlController()

    def invoke(self, gqlPayload: dict) -> dict:
        """
        @throws GraphQlException
        """
        try:
            return UpdateTaskService.getInstance().invoke(
                UpdateTaskDto.fromPrimitives({
                    **self.httpRequest.getMetadata(),
                    **self.getAuthUserOrFail(),
                    "taskUuid": gqlPayload.get(TasksKeyEnum.TASK_UUID) if gqlPayload.get(TasksKeyEnum.TASK_UUID) is not None else "",
                    "taskTitle": gqlPayload.get(TasksKeyEnum.TASK_TITLE) if gqlPayload.get(TasksKeyEnum.TASK_TITLE) is not None else "",
                    "taskStatus": gqlPayload.get(TasksKeyEnum.TASK_STATUS) if gqlPayload.get(TasksKeyEnum.TASK_STATUS) is not None else "",
                    "taskType": gqlPayload.get(TasksKeyEnum.TASK_TYPE) if gqlPayload.get(TasksKeyEnum.TASK_TYPE) is not None else "",
                    "targetDate": gqlPayload.get(TasksKeyEnum.TARGET_DATE) if gqlPayload.get(TasksKeyEnum.TARGET_DATE) is not None else "",
                    "taskDescription": gqlPayload.get(TasksKeyEnum.TASK_DESCRIPTION) if gqlPayload.get(TasksKeyEnum.TASK_DESCRIPTION) is not None else "",
                })
            ).toArray()
        except (FieldsException, UsersException, TasksException) as e:
            GraphQlException.clientAwareExceptionForWebonyx(e)
        except Exception as e:
            throwable = getThrowable(e)
            self.logTrait.logException(throwable, "UpdateTaskGraphQlController::invoke")
            self.mailTrait.sendMailOnThrowable(throwable, "UpdateTaskGraphQlController::invoke")
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
