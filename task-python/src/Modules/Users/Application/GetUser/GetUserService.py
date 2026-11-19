from Core.config.global_ import __

from Modules.Users.Application.GetUser.GetUserDto import GetUserDto
from Modules.Users.Application.GetUser.GetUserResultDto import GetUserResultDto
from Modules.Users.Domain.Enums.UsersMessageEnum import UsersMessageEnum
from Modules.Users.Infrastructure.Repositories.UsersReaderSqliteRepository import UsersReaderSqliteRepository
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class GetUserService:

    def __init__(self):
        self._usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance()

        self._getUserDto: GetUserDto

    @staticmethod
    def getInstance() -> "GetUserService":
        return GetUserService()

    def invoke(self, getUserDto: GetUserDto) -> GetUserResultDto:
        """
        @throws UsersException
        """
        self._getUserDto = getUserDto

        self._failIfWrongInput()

        userEntity = self._usersReaderSqliteRepository.getUserById(getUserDto.getAuthUserId())
        if not userEntity:
            UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, getUserDto.getUserUuid()))

        return GetUserResultDto.fromPrimitives({"user": userEntity.toPrimitives()})

    def _failIfWrongInput(self) -> None:
        """
        Solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
        @throws UsersException
        """
        if not self._getUserDto.getAuthUserId():
            UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN))

        userUuid = self._getUserDto.getUserUuid()
        if userUuid != self._getUserDto.getAuthUserUuid():
            UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, userUuid))
