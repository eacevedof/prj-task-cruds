from Core.config.global_ import __

from Modules.Users.Application.DeleteUser.DeleteUserDto import DeleteUserDto
from Modules.Users.Application.DeleteUser.DeleteUserResultDto import DeleteUserResultDto
from Modules.Users.Domain.Entities.UserEntity import UserEntity
from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Domain.Enums.UsersMessageEnum import UsersMessageEnum
from Modules.Users.Infrastructure.Repositories.UsersWriterSqliteRepository import UsersWriterSqliteRepository
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class DeleteUserService:
    """
    Borrado fisico. Las tasks del usuario se borran con el (FK ON DELETE CASCADE).
    """

    def __init__(self):
        self._usersWriterSqliteRepository = UsersWriterSqliteRepository.getInstance()

        self._deleteUserDto: DeleteUserDto

    @staticmethod
    def getInstance() -> "DeleteUserService":
        return DeleteUserService()

    def invoke(self, deleteUserDto: DeleteUserDto) -> DeleteUserResultDto:
        """
        @throws UsersException
        """
        self._deleteUserDto = deleteUserDto

        self._failIfWrongInput()

        return DeleteUserResultDto.fromPrimitives({
            "userUuid": deleteUserDto.getUserUuid(),
            "affected": self._usersWriterSqliteRepository.deleteUserById(
                UserEntity.fromPrimitives({
                    UsersKeyEnum.ID: deleteUserDto.getAuthUserId(),
                    UsersKeyEnum.USER_UUID: deleteUserDto.getAuthUserUuid(),
                })
            ),
        })

    def _failIfWrongInput(self) -> None:
        """
        Solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
        @throws UsersException
        """
        if self._deleteUserDto.getAuthUserId() < 1:
            UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN))

        userUuid = self._deleteUserDto.getUserUuid()
        if userUuid != self._deleteUserDto.getAuthUserUuid():
            UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, userUuid))
