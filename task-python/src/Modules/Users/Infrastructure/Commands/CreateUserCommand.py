from Modules.Shared.Infrastructure.Commands.AbstractConsoleCommand import AbstractConsoleCommand
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable
from Modules.Shared.Infrastructure.Exceptions.FieldsException import FieldsException

from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Application.CreateUser.CreateUserDto import CreateUserDto
from Modules.Users.Application.CreateUser.CreateUserService import CreateUserService
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class CreateUserCommand(AbstractConsoleCommand):
    """
    python cmd/console/main.py user-create --user_name=ana.lopez --user_email=ana@example.com --password=<password>
    Imprime la cuenta con su api_token en claro: es la unica vez que se ve.
    """

    def run(self) -> None:
        try:
            createUserResultDto = CreateUserService.getInstance().invoke(
                CreateUserDto.fromPrimitives({
                    "userName": self.getOptionValue(UsersKeyEnum.USER_NAME),
                    "userEmail": self.getOptionValue(UsersKeyEnum.USER_EMAIL),
                    "password": self.getOptionValue(UsersKeyEnum.PASSWORD),
                })
            )
            self.printResult(createUserResultDto.toArray())
        except Exception as e:
            if isinstance(e, (FieldsException, UsersException)):
                self.exitWithDomainError(e)
            self.exitWithUnexpectedError(getThrowable(e), "CreateUserCommand::run")
