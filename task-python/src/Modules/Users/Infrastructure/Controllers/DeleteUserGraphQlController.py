from Core.config.global_ import __

from Modules.Shared.Application.Primitives import Primitives
from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Controllers.AbstractHttpController import AbstractHttpController
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable

from Modules.GraphQl.Domain.Exceptions.GraphQlException import GraphQlException

from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Application.DeleteUser.DeleteUserDto import DeleteUserDto
from Modules.Users.Application.DeleteUser.DeleteUserService import DeleteUserService
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class DeleteUserGraphQlController(AbstractHttpController):

    @staticmethod
    def getInstance() -> "DeleteUserGraphQlController":
        return DeleteUserGraphQlController()

    def invoke(self, gqlPayload: Primitives | None = None) -> Primitives:
        """
        @throws GraphQlException
        """
        try:
            return DeleteUserService.getInstance().invoke(
                DeleteUserDto.fromPrimitives({
                    **self.httpRequest.getMetadata(),
                    **self.getAuthUserOrFail(),
                    "userUuid": (gqlPayload or {}).get(UsersKeyEnum.USER_UUID) or "",
                })
            ).toArray()
        except Exception as e:
            if isinstance(e, UsersException):
                GraphQlException.clientAwareExceptionForWebonyx(e)
            throwable = getThrowable(e)
            self.logTrait.logException(throwable, "DeleteUserGraphQlController::invoke")
            self.mailTrait.sendMailOnThrowable(throwable, "DeleteUserGraphQlController::invoke")
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
