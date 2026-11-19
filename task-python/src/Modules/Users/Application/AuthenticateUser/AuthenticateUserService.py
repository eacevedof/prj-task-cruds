from Core.config.global_ import __

from Modules.Shared.Infrastructure.Components.Token.TokenGenerator import TokenGenerator

from Modules.Users.Application.AuthenticateUser.AuthenticateUserDto import AuthenticateUserDto
from Modules.Users.Application.AuthenticateUser.AuthenticateUserResultDto import AuthenticateUserResultDto
from Modules.Users.Domain.Entities.UserEntity import UserEntity
from Modules.Users.Domain.Enums.UsersMessageEnum import UsersMessageEnum
from Modules.Users.Infrastructure.Repositories.UsersReaderSqliteRepository import UsersReaderSqliteRepository
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class AuthenticateUserService:
    """
    Bearer token -> usuario. Mismo error para token ausente y token desconocido: no se da pista de cual fallo.
    """

    def __init__(self):
        self._tokenGenerator = TokenGenerator.getInstance()
        self._usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance()

        self._authenticateUserDto: AuthenticateUserDto

    @staticmethod
    def getInstance() -> "AuthenticateUserService":
        return AuthenticateUserService()

    def invoke(self, authenticateUserDto: AuthenticateUserDto) -> AuthenticateUserResultDto:
        """
        @throws UsersException
        """
        self._authenticateUserDto = authenticateUserDto

        self._failIfWrongInput()
        userEntity = self._tryToGetUserByApiTokenOrFail()

        return AuthenticateUserResultDto.fromPrimitives({
            "userId": userEntity.getId(),
            "userUuid": userEntity.getUserUuid(),
        })

    def _failIfWrongInput(self) -> None:
        """
        @throws UsersException
        """
        if not self._authenticateUserDto.getApiToken():
            UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN))

    def _tryToGetUserByApiTokenOrFail(self) -> UserEntity:
        """
        @throws UsersException
        """
        userEntity = self._usersReaderSqliteRepository.getUserByApiTokenHash(
            self._tokenGenerator.getTokenHash(self._authenticateUserDto.getApiToken())
        )
        if not userEntity:
            UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN))
        return userEntity
