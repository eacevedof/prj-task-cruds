from Core.config.global_ import __

from Modules.Shared.Infrastructure.Components.Date.DateTimer import DateTimer
from Modules.Shared.Infrastructure.Components.Token.TokenGenerator import TokenGenerator

from Modules.Users.Application.RegenerateUserApiToken.RegenerateUserApiTokenDto import RegenerateUserApiTokenDto
from Modules.Users.Application.RegenerateUserApiToken.RegenerateUserApiTokenResultDto import RegenerateUserApiTokenResultDto
from Modules.Users.Domain.Entities.UserEntity import UserEntity
from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Domain.Enums.UsersMessageEnum import UsersMessageEnum
from Modules.Users.Infrastructure.Repositories.UsersWriterSqliteRepository import UsersWriterSqliteRepository
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class RegenerateUserApiTokenService:
    """
    Rota el token del propio usuario (p.ej. si se ha filtrado): el anterior deja de valer y el nuevo se
    devuelve en claro solo en esta respuesta.
    """

    def __init__(self):
        self._dateTimer = DateTimer.getInstance()
        self._tokenGenerator = TokenGenerator.getInstance()
        self._usersWriterSqliteRepository = UsersWriterSqliteRepository.getInstance()

        self._regenerateUserApiTokenDto: RegenerateUserApiTokenDto

    @staticmethod
    def getInstance() -> "RegenerateUserApiTokenService":
        return RegenerateUserApiTokenService()

    def invoke(self, regenerateUserApiTokenDto: RegenerateUserApiTokenDto) -> RegenerateUserApiTokenResultDto:
        """
        @throws UsersException
        """
        self._regenerateUserApiTokenDto = regenerateUserApiTokenDto

        self._failIfWrongInput()

        apiToken = self._tokenGenerator.getNewToken()
        self._usersWriterSqliteRepository.updateApiTokenHashById(
            UserEntity.fromPrimitives({
                UsersKeyEnum.ID: regenerateUserApiTokenDto.getAuthUserId(),
                UsersKeyEnum.API_TOKEN_HASH: self._tokenGenerator.getTokenHash(apiToken),
                UsersKeyEnum.UPDATED_AT: self._dateTimer.getNowDtIntoTargetTz(),
            })
        )

        return RegenerateUserApiTokenResultDto.fromPrimitives({
            "userUuid": regenerateUserApiTokenDto.getUserUuid(),
            "apiToken": apiToken,
        })

    def _failIfWrongInput(self) -> None:
        """
        Solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
        @throws UsersException
        """
        if self._regenerateUserApiTokenDto.getAuthUserId() < 1:
            UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN))

        userUuid = self._regenerateUserApiTokenDto.getUserUuid()
        if userUuid != self._regenerateUserApiTokenDto.getAuthUserUuid():
            UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, userUuid))
