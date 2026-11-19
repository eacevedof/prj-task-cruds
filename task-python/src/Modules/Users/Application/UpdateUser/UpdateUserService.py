from Core.config.global_ import __

from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Components.Date.DateTimer import DateTimer
from Modules.Shared.Infrastructure.Traits.ErrorStackTrait import ErrorStackTrait
from Modules.Shared.Infrastructure.Exceptions.FieldsException import FieldsException

from Modules.Users.Application.UpdateUser.UpdateUserDto import UpdateUserDto
from Modules.Users.Application.UpdateUser.UpdateUserResultDto import UpdateUserResultDto
from Modules.Users.Domain.Entities.UserEntity import UserEntity
from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Domain.Enums.UsersMessageEnum import UsersMessageEnum
from Modules.Users.Domain.Services.UserFieldsValidator import UserFieldsValidator
from Modules.Users.Infrastructure.Repositories.UsersReaderSqliteRepository import UsersReaderSqliteRepository
from Modules.Users.Infrastructure.Repositories.UsersWriterSqliteRepository import UsersWriterSqliteRepository
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class UpdateUserService:
    """
    PUT: reemplazo completo, user_name y user_email obligatorios (mismas reglas que el alta).
    """

    def __init__(self):
        self._errorStackTrait = ErrorStackTrait.getInstance()
        self._dateTimer = DateTimer.getInstance()
        self._usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance()
        self._usersWriterSqliteRepository = UsersWriterSqliteRepository.getInstance()

        self._updateUserDto: UpdateUserDto
        self._userEntity: UserEntity

    @staticmethod
    def getInstance() -> "UpdateUserService":
        return UpdateUserService()

    def invoke(self, updateUserDto: UpdateUserDto) -> UpdateUserResultDto:
        """
        @throws FieldsException
        @throws UsersException
        """
        self._updateUserDto = updateUserDto

        self._failIfWrongInput()
        self._failIfWrongFields()

        # autenticado = existe: su id ya viene resuelto
        self._userEntity = UserEntity.fromPrimitives({
            UsersKeyEnum.ID: updateUserDto.getAuthUserId(),
            UsersKeyEnum.USER_UUID: updateUserDto.getAuthUserUuid(),
            UsersKeyEnum.USER_NAME: updateUserDto.getUserName(),
            UsersKeyEnum.USER_EMAIL: updateUserDto.getUserEmail(),
            UsersKeyEnum.UPDATED_AT: self._dateTimer.getNowDtIntoTargetTz(),
        })
        self._failIfUserNameOrEmailExistsInOtherUser()

        self._usersWriterSqliteRepository.updateUserById(self._userEntity)

        userEntity = self._usersReaderSqliteRepository.getUserById(self._userEntity.getId() or 0)
        return UpdateUserResultDto.fromPrimitives({
            "user": userEntity.toPrimitives() if userEntity else {},
        })

    def _failIfWrongInput(self) -> None:
        """
        Solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe).
        @throws UsersException
        """
        if self._updateUserDto.getAuthUserId() < 1:
            UsersException.unauthorizedCustom(__(UsersMessageEnum.MISSING_OR_INVALID_API_TOKEN))

        userUuid = self._updateUserDto.getUserUuid()
        if userUuid != self._updateUserDto.getAuthUserUuid():
            UsersException.notFoundCustom(__(UsersMessageEnum.USER_X_NOT_FOUND, userUuid))

    def _failIfWrongFields(self) -> None:
        """
        @throws FieldsException
        """
        errors = UserFieldsValidator.getInstance().getErrors(
            self._updateUserDto.getUserName(),
            self._updateUserDto.getUserEmail()
        )
        if not errors:
            return

        FieldsException.badRequestCustom(
            self._errorStackTrait.getErrorStackMessage(
                __(SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors
            ))

    def _failIfUserNameOrEmailExistsInOtherUser(self) -> None:
        """
        @throws UsersException
        """
        if self._usersReaderSqliteRepository.doesUserNameExistInOtherUser(self._userEntity):
            UsersException.conflictCustom(
                __(UsersMessageEnum.USER_NAME_X_ALREADY_EXISTS, self._userEntity.getUserName())
            )
        if self._usersReaderSqliteRepository.doesUserEmailExistInOtherUser(self._userEntity):
            UsersException.conflictCustom(
                __(UsersMessageEnum.EMAIL_X_ALREADY_EXISTS, self._userEntity.getUserEmail())
            )
