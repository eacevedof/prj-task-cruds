from Core.config.global_ import __

from Modules.Users.Application.AuthenticateConsoleUser.AuthenticateConsoleUserDto import AuthenticateConsoleUserDto
from Modules.Users.Application.AuthenticateUser.AuthenticateUserResultDto import AuthenticateUserResultDto
from Modules.Users.Domain.Entities.UserEntity import UserEntity
from Modules.Users.Domain.Enums.UsersMessageEnum import UsersMessageEnum
from Modules.Users.Infrastructure.Repositories.UsersReaderSqliteRepository import UsersReaderSqliteRepository
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class AuthenticateConsoleUserService:
    """
    Misma salida que AuthenticateUser para que los casos de uso no distingan si la peticion llega por API o por
    consola.
    """

    def __init__(self):
        self._usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance()

        self._authenticateConsoleUserDto: AuthenticateConsoleUserDto

    @staticmethod
    def getInstance() -> "AuthenticateConsoleUserService":
        return AuthenticateConsoleUserService()

    def invoke(self, authenticateConsoleUserDto: AuthenticateConsoleUserDto) -> AuthenticateUserResultDto:
        """
        @throws UsersException
        """
        self._authenticateConsoleUserDto = authenticateConsoleUserDto

        self._failIfWrongInput()
        userEntity = self._tryToGetUserOrFail()

        return AuthenticateUserResultDto.fromPrimitives({
            "userId": userEntity.getId(),
            "userUuid": userEntity.getUserUuid(),
        })

    def _failIfWrongInput(self) -> None:
        """
        @throws UsersException
        """
        if not self._authenticateConsoleUserDto.getUserUuid():
            UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, ""))

    def _tryToGetUserOrFail(self) -> UserEntity:
        """
        @throws UsersException
        """
        userUuid = self._authenticateConsoleUserDto.getUserUuid()
        userEntity = self._usersReaderSqliteRepository.getUserByUserUuid(userUuid)
        if not userEntity:
            UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, userUuid))
        return userEntity
