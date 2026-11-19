from Core.config.global_ import __

from Modules.Shared.Application.Primitives import Primitives
from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Controllers.AbstractHttpController import AbstractHttpController
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable

from Modules.GraphQl.Domain.Exceptions.GraphQlException import GraphQlException

from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Application.LogInUser.LogInUserDto import LogInUserDto
from Modules.Users.Application.LogInUser.LogInUserService import LogInUserService
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class LogInUserGraphQlController(AbstractHttpController):

    @staticmethod
    def getInstance() -> "LogInUserGraphQlController":
        return LogInUserGraphQlController()

    def invoke(self, gqlPayload: Primitives | None = None) -> Primitives:
        """
        @throws GraphQlException
        """
        try:
            return LogInUserService.getInstance().invoke(
                LogInUserDto.fromPrimitives({
                    **self.httpRequest.getMetadata(),
                    "userName": (gqlPayload or {}).get(UsersKeyEnum.USER_NAME) or "",
                    "password": (gqlPayload or {}).get(UsersKeyEnum.PASSWORD) or "",
                })
            ).toArray()
        except Exception as e:
            if isinstance(e, UsersException):
                GraphQlException.clientAwareExceptionForWebonyx(e)
            throwable = getThrowable(e)
            self.logTrait.logException(throwable, "LogInUserGraphQlController::invoke")
            self.mailTrait.sendMailOnThrowable(throwable, "LogInUserGraphQlController::invoke")
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
