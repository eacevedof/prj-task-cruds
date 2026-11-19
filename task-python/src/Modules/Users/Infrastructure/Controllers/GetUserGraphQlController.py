from Core.config.global_ import __

from Modules.Shared.Application.Primitives import Primitives
from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Controllers.AbstractHttpController import AbstractHttpController
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable

from Modules.GraphQl.Domain.Exceptions.GraphQlException import GraphQlException

from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Application.GetUser.GetUserDto import GetUserDto
from Modules.Users.Application.GetUser.GetUserService import GetUserService
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class GetUserGraphQlController(AbstractHttpController):

    @staticmethod
    def getInstance() -> "GetUserGraphQlController":
        return GetUserGraphQlController()

    def invoke(self, gqlPayload: Primitives | None = None) -> Primitives:
        """
        @throws GraphQlException
        """
        try:
            return GetUserService.getInstance().invoke(
                GetUserDto.fromPrimitives({
                    **self.httpRequest.getMetadata(),
                    **self.getAuthUserOrFail(),
                    "userUuid": (gqlPayload or {}).get(UsersKeyEnum.USER_UUID) or "",
                })
            ).toArray()
        except Exception as e:
            if isinstance(e, UsersException):
                GraphQlException.clientAwareExceptionForWebonyx(e)
            throwable = getThrowable(e)
            self.logTrait.logException(throwable, "GetUserGraphQlController::invoke")
            self.mailTrait.sendMailOnThrowable(throwable, "GetUserGraphQlController::invoke")
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
