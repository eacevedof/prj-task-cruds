from Core.config.global_ import __

from Modules.Shared.Application.Primitives import Primitives
from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Controllers.AbstractHttpController import AbstractHttpController
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable
from Modules.Shared.Infrastructure.Exceptions.FieldsException import FieldsException

from Modules.GraphQl.Domain.Exceptions.GraphQlException import GraphQlException

from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Application.CreateUser.CreateUserDto import CreateUserDto
from Modules.Users.Application.CreateUser.CreateUserService import CreateUserService
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class CreateUserGraphQlController(AbstractHttpController):

    @staticmethod
    def getInstance() -> "CreateUserGraphQlController":
        return CreateUserGraphQlController()

    def invoke(self, gqlPayload: Primitives | None = None) -> Primitives:
        """
        @throws GraphQlException
        """
        try:
            return CreateUserService.getInstance().invoke(
                CreateUserDto.fromPrimitives({
                    **self.httpRequest.getMetadata(),
                    "userName": (gqlPayload or {}).get(UsersKeyEnum.USER_NAME) or "",
                    "userEmail": (gqlPayload or {}).get(UsersKeyEnum.USER_EMAIL) or "",
                    "password": (gqlPayload or {}).get(UsersKeyEnum.PASSWORD) or "",
                })
            ).toArray()
        except Exception as e:
            if isinstance(e, (FieldsException, UsersException)):
                GraphQlException.clientAwareExceptionForWebonyx(e)
            throwable = getThrowable(e)
            self.logTrait.logException(throwable, "CreateUserGraphQlController::invoke")
            self.mailTrait.sendMailOnThrowable(throwable, "CreateUserGraphQlController::invoke")
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
