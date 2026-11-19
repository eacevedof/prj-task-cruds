from Core.config.global_ import __

from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Controllers.AbstractHttpController import AbstractHttpController
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable
from Modules.Shared.Infrastructure.Exceptions.FieldsException import FieldsException

from Modules.GraphQl.Domain.Exceptions.GraphQlException import GraphQlException

from Modules.Tasks.Application.CreateTask.CreateTaskDto import CreateTaskDto
from Modules.Tasks.Application.CreateTask.CreateTaskService import CreateTaskService
from Modules.Tasks.Domain.Enums.TasksKeyEnum import TasksKeyEnum
from Modules.Tasks.Domain.Exceptions.TasksException import TasksException

from Modules.Users.Domain.Exceptions.UsersException import UsersException


class CreateTaskGraphQlController(AbstractHttpController):
    """
    Mutation createTask (src/Modules/Tasks/Infrastructure/Providers/tasks.graphql). El 401 de la autenticacion llega como UsersException.
    """

    @staticmethod
    def getInstance() -> "CreateTaskGraphQlController":
        return CreateTaskGraphQlController()

    def invoke(self, gqlPayload: dict) -> dict:
        """
        @throws GraphQlException
        """
        try:
            return CreateTaskService.getInstance().invoke(
                CreateTaskDto.fromPrimitives({
                    **self.httpRequest.getMetadata(),
                    **self.getAuthUserOrFail(),
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
            self.logTrait.logException(throwable, "CreateTaskGraphQlController::invoke")
            self.mailTrait.sendMailOnThrowable(throwable, "CreateTaskGraphQlController::invoke")
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
