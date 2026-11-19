from Core.config.global_ import __

from Modules.Users.Application.ListUsers.ListUsersDto import ListUsersDto
from Modules.Users.Application.ListUsers.ListUsersResultDto import ListUsersResultDto
from Modules.Users.Domain.Enums.UsersMessageEnum import UsersMessageEnum
from Modules.Users.Infrastructure.Repositories.UsersReaderSqliteRepository import UsersReaderSqliteRepository
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class ListUsersService:
    """
    Cada token solo ve su propio usuario: la lista tiene como mucho un elemento.
    """

    def __init__(self):
        self._usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance()

        self._listUsersDto: ListUsersDto

    @staticmethod
    def getInstance() -> "ListUsersService":
        return ListUsersService()

    def invoke(self, listUsersDto: ListUsersDto) -> ListUsersResultDto:
        """
        @throws UsersException
        """
        self._listUsersDto = listUsersDto

        self._failIfWrongInput()

        userEntity = self._usersReaderSqliteRepository.getUserById(listUsersDto.getAuthUserId())
        return ListUsersResultDto.fromPrimitives({
            "users": [userEntity.toPrimitives()] if userEntity else [],
        })

    def _failIfWrongInput(self) -> None:
        """
        @throws UsersException
        """
        if self._listUsersDto.getAuthUserId() < 1:
            UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN))
