from Core.config.global_ import __

from Modules.Shared.Application.Primitives import Primitives
from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Controllers.AbstractHttpController import AbstractHttpController
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable
from Modules.Shared.Infrastructure.Exceptions.FieldsException import FieldsException

from Modules.GraphQl.Domain.Exceptions.GraphQlException import GraphQlException

from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Application.UpdateUser.UpdateUserDto import UpdateUserDto
from Modules.Users.Application.UpdateUser.UpdateUserService import UpdateUserService
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class UpdateUserGraphQlController(AbstractHttpController):

    @staticmethod
    def getInstance() -> "UpdateUserGraphQlController":
        return UpdateUserGraphQlController()

    def invoke(self, gqlPayload: Primitives | None = None) -> Primitives:
        """
        @throws GraphQlException
        """
        try:
            return UpdateUserService.getInstance().invoke(
                UpdateUserDto.fromPrimitives({
                    **self.httpRequest.getMetadata(),
                    **self.getAuthUserOrFail(),
                    "userUuid": (gqlPayload or {}).get(UsersKeyEnum.USER_UUID) or "",
                    "userName": (gqlPayload or {}).get(UsersKeyEnum.USER_NAME) or "",
                    "userEmail": (gqlPayload or {}).get(UsersKeyEnum.USER_EMAIL) or "",
                })
            ).toArray()
        except Exception as e:
            if isinstance(e, (FieldsException, UsersException)):
                GraphQlException.clientAwareExceptionForWebonyx(e)
            throwable = getThrowable(e)
            self.logTrait.logException(throwable, "UpdateUserGraphQlController::invoke")
            self.mailTrait.sendMailOnThrowable(throwable, "UpdateUserGraphQlController::invoke")
            GraphQlException.internalServerError(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
