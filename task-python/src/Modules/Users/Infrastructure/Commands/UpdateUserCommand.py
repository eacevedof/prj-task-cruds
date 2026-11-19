from Modules.Shared.Infrastructure.Commands.AbstractConsoleCommand import AbstractConsoleCommand
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable
from Modules.Shared.Infrastructure.Exceptions.FieldsException import FieldsException

from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Application.UpdateUser.UpdateUserDto import UpdateUserDto
from Modules.Users.Application.UpdateUser.UpdateUserService import UpdateUserService
from Modules.Users.Infrastructure.Traits.ConsoleAuthUserTrait import ConsoleAuthUserTrait
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class UpdateUserCommand(AbstractConsoleCommand):
    """
    python cmd/console/main.py user-update --user_uuid=<uuid> --user_name=ana.lopez --user_email=ana@example.com
    Reemplazo completo, como el PUT: user_name y user_email obligatorios.
    Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid (ConsoleAuthUserTrait).
    """

    def __init__(self, input: list[str]):
        super().__init__(input)
        self._consoleAuthUserTrait = ConsoleAuthUserTrait.getInstance()

    def run(self) -> None:
        try:
            userUuid = self.getOptionValue(UsersKeyEnum.USER_UUID)
            updateUserResultDto = UpdateUserService.getInstance().invoke(
                UpdateUserDto.fromPrimitives({
                    **self._consoleAuthUserTrait.tryToGetConsoleAuthUserPrimitivesOrFail(userUuid),
                    "userUuid": userUuid,
                    "userName": self.getOptionValue(UsersKeyEnum.USER_NAME),
                    "userEmail": self.getOptionValue(UsersKeyEnum.USER_EMAIL),
                })
            )
            self.printResult(updateUserResultDto.toArray())
        except Exception as e:
            if isinstance(e, (FieldsException, UsersException)):
                self.exitWithDomainError(e)
            self.exitWithUnexpectedError(getThrowable(e), "UpdateUserCommand::run")
