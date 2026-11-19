from Modules.Shared.Infrastructure.Components.HttpResponder.HttpResponder import HttpResponder
from Modules.Shared.Infrastructure.Controllers.AbstractRestController import AbstractRestController
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable

from Modules.Users.Application.LogInUser.LogInUserDto import LogInUserDto
from Modules.Users.Application.LogInUser.LogInUserService import LogInUserService
from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class LogInUserRestController(AbstractRestController):
    """
    POST /api/v1/log-in  body: {user_name, password}  (publico: devuelve el usuario y un api_token nuevo)
    """

    @staticmethod
    def getInstance() -> "LogInUserRestController":
        return LogInUserRestController()

    def invoke(self) -> HttpResponder:
        try:
            logInUserResultDto = LogInUserService.getInstance().invoke(
                LogInUserDto.fromPrimitives({
                    **self.httpRequest.getMetadata(),
                    "userName": self.httpRequest.getPostString(UsersKeyEnum.USER_NAME),
                    "password": self.httpRequest.getPostString(UsersKeyEnum.PASSWORD),
                })
            )
            return self.respondOk(logInUserResultDto.toArray())
        except Exception as e:
            if isinstance(e, UsersException):
                return self.respondDomainError(e)
            return self.respondUnexpectedError(getThrowable(e), "LogInUserRestController::invoke")
