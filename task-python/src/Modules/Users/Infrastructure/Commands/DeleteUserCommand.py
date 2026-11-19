from Modules.Shared.Infrastructure.Commands.AbstractConsoleCommand import AbstractConsoleCommand
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable

from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Application.DeleteUser.DeleteUserDto import DeleteUserDto
from Modules.Users.Application.DeleteUser.DeleteUserService import DeleteUserService
from Modules.Users.Infrastructure.Traits.ConsoleAuthUserTrait import ConsoleAuthUserTrait
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class DeleteUserCommand(AbstractConsoleCommand):
    """
    python cmd/console/main.py user-delete --user_uuid=<uuid>
    Borrado fisico: se lleva sus tareas (FK ON DELETE CASCADE). No pide confirmacion.
    Quien tiene la consola del contenedor actua como el dueño de la cuenta: se autentica por el user_uuid (ConsoleAuthUserTrait).
    """

    def __init__(self, input: list[str]):
        super().__init__(input)
        self._consoleAuthUserTrait = ConsoleAuthUserTrait.getInstance()

    def run(self) -> None:
        try:
            userUuid = self.getOptionValue(UsersKeyEnum.USER_UUID)
            deleteUserResultDto = DeleteUserService.getInstance().invoke(
                DeleteUserDto.fromPrimitives({
                    **self._consoleAuthUserTrait.tryToGetConsoleAuthUserPrimitivesOrFail(userUuid),
                    "userUuid": userUuid,
                })
            )
            self.printResult(deleteUserResultDto.toArray())
        except Exception as e:
            if isinstance(e, UsersException):
                self.exitWithDomainError(e)
            self.exitWithUnexpectedError(getThrowable(e), "DeleteUserCommand::run")
