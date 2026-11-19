from Modules.Shared.Infrastructure.Commands.AbstractConsoleCommand import AbstractConsoleCommand
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable

from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Application.GetUser.GetUserDto import GetUserDto
from Modules.Users.Application.GetUser.GetUserService import GetUserService
from Modules.Users.Infrastructure.Traits.ConsoleAuthUserTrait import ConsoleAuthUserTrait
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class GetUserCommand(AbstractConsoleCommand):
    """
    python cmd/console/main.py user-get --user_uuid=<uuid>
    Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid (ConsoleAuthUserTrait).
    """

    def __init__(self, input: list[str]):
        super().__init__(input)
        self._consoleAuthUserTrait = ConsoleAuthUserTrait.getInstance()

    def run(self) -> None:
        try:
            userUuid = self.getOptionValue(UsersKeyEnum.USER_UUID)
            getUserResultDto = GetUserService.getInstance().invoke(
                GetUserDto.fromPrimitives({
                    **self._consoleAuthUserTrait.tryToGetConsoleAuthUserPrimitivesOrFail(userUuid),
                    "userUuid": userUuid,
                })
            )
            self.printResult(getUserResultDto.toArray())
        except Exception as e:
            if isinstance(e, UsersException):
                self.exitWithDomainError(e)
            self.exitWithUnexpectedError(getThrowable(e), "GetUserCommand::run")
