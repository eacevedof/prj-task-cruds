from Modules.Shared.Infrastructure.Commands.AbstractConsoleCommand import AbstractConsoleCommand
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable

from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Application.RegenerateUserApiToken.RegenerateUserApiTokenDto import RegenerateUserApiTokenDto
from Modules.Users.Application.RegenerateUserApiToken.RegenerateUserApiTokenService import RegenerateUserApiTokenService
from Modules.Users.Infrastructure.Traits.ConsoleAuthUserTrait import ConsoleAuthUserTrait
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class RegenerateUserApiTokenCommand(AbstractConsoleCommand):
    """
    python cmd/console/main.py user-regenerate-api-token --user_uuid=<uuid>
    El token anterior deja de valer; el nuevo se imprime en claro solo aqui.
    Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid (ConsoleAuthUserTrait).
    """

    def __init__(self, input: list[str]):
        super().__init__(input)
        self._consoleAuthUserTrait = ConsoleAuthUserTrait.getInstance()

    def run(self) -> None:
        try:
            userUuid = self.getOptionValue(UsersKeyEnum.USER_UUID)
            regenerateUserApiTokenResultDto = RegenerateUserApiTokenService.getInstance().invoke(
                RegenerateUserApiTokenDto.fromPrimitives({
                    **self._consoleAuthUserTrait.tryToGetConsoleAuthUserPrimitivesOrFail(userUuid),
                    "userUuid": userUuid,
                })
            )
            self.printResult(regenerateUserApiTokenResultDto.toArray())
        except Exception as e:
            if isinstance(e, UsersException):
                self.exitWithDomainError(e)
            self.exitWithUnexpectedError(getThrowable(e), "RegenerateUserApiTokenCommand::run")
