from Core.config.global_ import __

from Modules.Shared.Infrastructure.Components.Date.DateTimer import DateTimer
from Modules.Shared.Infrastructure.Components.Password.PasswordHasher import PasswordHasher
from Modules.Shared.Infrastructure.Components.Token.TokenGenerator import TokenGenerator

from Modules.Users.Application.LogInUser.LogInUserDto import LogInUserDto
from Modules.Users.Application.LogInUser.LogInUserResultDto import LogInUserResultDto
from Modules.Users.Domain.Entities.UserEntity import UserEntity
from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Domain.Enums.UsersMessageEnum import UsersMessageEnum
from Modules.Users.Infrastructure.Repositories.UsersReaderSqliteRepository import UsersReaderSqliteRepository
from Modules.Users.Infrastructure.Repositories.UsersWriterSqliteRepository import UsersWriterSqliteRepository
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class LogInUserService:
    """
    Login con user_name + password: devuelve un api_token NUEVO y el anterior deja de valer (como regenerar).
    Usuario inexistente, sin password o password erronea responden lo mismo (401, mismo mensaje y mismo
    tiempo): no se revela que user_name existen.
    """

    def __init__(self):
        self._dateTimer = DateTimer.getInstance()
        self._passwordHasher = PasswordHasher.getInstance()
        self._tokenGenerator = TokenGenerator.getInstance()
        self._usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance()
        self._usersWriterSqliteRepository = UsersWriterSqliteRepository.getInstance()

        self._logInUserDto: LogInUserDto
        self._passwordHash = ""

    @staticmethod
    def getInstance() -> "LogInUserService":
        return LogInUserService()

    def invoke(self, logInUserDto: LogInUserDto) -> LogInUserResultDto:
        """
        @throws UsersException
        """
        self._logInUserDto = logInUserDto

        self._failIfWrongInput()
        userId = self._tryToGetUserByCredentialsOrFail().getId() or 0
        now = self._dateTimer.getNowDtIntoTargetTz()

        self._updatePasswordHashIfNeedsRehash(userId, now)

        apiToken = self._tokenGenerator.getNewToken()
        self._usersWriterSqliteRepository.updateApiTokenHashById(
            UserEntity.fromPrimitives({
                UsersKeyEnum.ID: userId,
                UsersKeyEnum.API_TOKEN_HASH: self._tokenGenerator.getTokenHash(apiToken),
                UsersKeyEnum.UPDATED_AT: now,
            })
        )

        # releido: el login cambia updated_at
        userEntity = self._usersReaderSqliteRepository.getUserById(userId)
        return LogInUserResultDto.fromPrimitives({
            "user": userEntity.toPrimitives() if userEntity else {},
            "apiToken": apiToken,
        })

    def _failIfWrongInput(self) -> None:
        """
        Sin user_name o sin password no hay nada que comprobar: mismo 401 que unas credenciales erroneas.
        @throws UsersException
        """
        if not self._logInUserDto.getUserName() or not self._logInUserDto.getPassword():
            UsersException.unauthorizedCustom(__(UsersMessageEnum.INVALID_USER_NAME_OR_PASSWORD))

    def _tryToGetUserByCredentialsOrFail(self) -> UserEntity:
        """
        El hash vacio (usuario inexistente o sin password) tambien pasa por la verificacion: mismo tiempo de
        respuesta que una password erronea.
        @throws UsersException
        """
        userEntity = self._usersReaderSqliteRepository.getUserByUserName(self._logInUserDto.getUserName())
        self._passwordHash = (
            self._usersReaderSqliteRepository.getPasswordHashByUserId(userEntity.getId() or 0)
            if userEntity else ""
        )

        if not self._passwordHasher.isPasswordValid(self._logInUserDto.getPassword(), self._passwordHash) or not userEntity:
            UsersException.unauthorizedCustom(__(UsersMessageEnum.INVALID_USER_NAME_OR_PASSWORD))
        return userEntity

    def _updatePasswordHashIfNeedsRehash(self, userId: int, now: str) -> None:
        if not self._passwordHasher.doesPasswordHashNeedRehash(self._passwordHash):
            return

        self._usersWriterSqliteRepository.updatePasswordHashById(
            UserEntity.fromPrimitives({
                UsersKeyEnum.ID: userId,
                UsersKeyEnum.PASSWORD_HASH: self._passwordHasher.getPasswordHash(self._logInUserDto.getPassword()),
                UsersKeyEnum.UPDATED_AT: now,
            })
        )
