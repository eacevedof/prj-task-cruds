from Core.config.global_ import __

from Modules.Shared.Application.Primitives import Primitives
from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Controllers.AbstractHttpController import AbstractHttpController
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable

from Modules.GraphQl.Domain.Exceptions.GraphQlException import GraphQlException

from Modules.Users.Application.ListUsers.ListUsersDto import ListUsersDto
from Modules.Users.Application.ListUsers.ListUsersService import ListUsersService
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class ListUsersGraphQlController(AbstractHttpController):

    @staticmethod
    def getInstance() -> "ListUsersGraphQlController":
        return ListUsersGraphQlController()

    def invoke(self, gqlPayload: Primitives | None = None) -> list[Primitives]:
        """
        @throws GraphQlException
        """
        try:
            return ListUsersService.getInstance().invoke(
                ListUsersDto.fromPrimitives({
                    **self.httpRequest.getMetadata(),
                    **self.getAuthUserOrFail(),
                })
            ).toArray()
        except Exception as e:
            if isinstance(e, UsersException):
                GraphQlException.clientAwareExceptionForWebonyx(e)
            throwable = getThrowable(e)
            self.logTrait.logException(throwable, "ListUsersGraphQlController::invoke")
            self.mailTrait.sendMailOnThrowable(throwable, "ListUsersGraphQlController::invoke")
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
