from Modules.Shared.Application.Primitives import Primitives

from Modules.Users.Application.AuthenticateConsoleUser.AuthenticateConsoleUserDto import AuthenticateConsoleUserDto
from Modules.Users.Application.AuthenticateConsoleUser.AuthenticateConsoleUserService import AuthenticateConsoleUserService


class ConsoleAuthUserTrait:
    """
    Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por su user_uuid, sin
    token.
    """

    @staticmethod
    def getInstance() -> "ConsoleAuthUserTrait":
        return ConsoleAuthUserTrait()

    def tryToGetConsoleAuthUserPrimitivesOrFail(self, userUuid: str) -> Primitives:
        """
        @throws UsersException
        """
        return AuthenticateConsoleUserService.getInstance().invoke(
            AuthenticateConsoleUserDto.fromPrimitives({"userUuid": userUuid})
        ).toAuthPrimitives()
