from Core.config.global_ import __

from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Components.Date.DateTimer import DateTimer
from Modules.Shared.Infrastructure.Components.Password.PasswordHasher import PasswordHasher
from Modules.Shared.Infrastructure.Components.Token.TokenGenerator import TokenGenerator
from Modules.Shared.Infrastructure.Components.Uuid.UuidGenerator import UuidGenerator
from Modules.Shared.Infrastructure.Traits.ErrorStackTrait import ErrorStackTrait
from Modules.Shared.Infrastructure.Exceptions.FieldsException import FieldsException

from Modules.Users.Application.CreateUser.CreateUserDto import CreateUserDto
from Modules.Users.Application.CreateUser.CreateUserResultDto import CreateUserResultDto
from Modules.Users.Domain.Entities.UserEntity import UserEntity
from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum
from Modules.Users.Domain.Enums.UsersMessageEnum import UsersMessageEnum
from Modules.Users.Domain.Services.UserFieldsValidator import UserFieldsValidator
from Modules.Users.Infrastructure.Repositories.UsersReaderSqliteRepository import UsersReaderSqliteRepository
from Modules.Users.Infrastructure.Repositories.UsersWriterSqliteRepository import UsersWriterSqliteRepository
from Modules.Users.Domain.Exceptions.UsersException import UsersException


class CreateUserService:
    """
    Alta abierta (sin token): devuelve el primer api_token en claro; despues, el login o regenerar dan uno nuevo.
    """

    def __init__(self):
        self._errorStackTrait = ErrorStackTrait.getInstance()
        self._dateTimer = DateTimer.getInstance()
        self._passwordHasher = PasswordHasher.getInstance()
        self._tokenGenerator = TokenGenerator.getInstance()
        self._usersReaderSqliteRepository = UsersReaderSqliteRepository.getInstance()
        self._usersWriterSqliteRepository = UsersWriterSqliteRepository.getInstance()

        self._createUserDto: CreateUserDto

    @staticmethod
    def getInstance() -> "CreateUserService":
        return CreateUserService()

    def invoke(self, createUserDto: CreateUserDto) -> CreateUserResultDto:
        """
        @throws FieldsException
        @throws UsersException
        """
        self._createUserDto = createUserDto

        self._failIfWrongInput()
        self._failIfUserNameOrEmailExists()

        apiToken = self._tokenGenerator.getNewToken()
        userId = self._usersWriterSqliteRepository.insertUser(
            UserEntity.fromPrimitives({
                UsersKeyEnum.USER_UUID: UuidGenerator.getInstance().getUniqueIdWithPrefix(UserEntity.USER_UUID_PREFIX),
                UsersKeyEnum.USER_NAME: createUserDto.getUserName(),
                UsersKeyEnum.USER_EMAIL: createUserDto.getUserEmail(),
                UsersKeyEnum.PASSWORD_HASH: self._passwordHasher.getPasswordHash(createUserDto.getPassword()),
                UsersKeyEnum.API_TOKEN_HASH: self._tokenGenerator.getTokenHash(apiToken),
                UsersKeyEnum.CREATED_AT: self._dateTimer.getNowDtIntoTargetTz(),
            })
        )

        userEntity = self._usersReaderSqliteRepository.getUserById(userId)
        return CreateUserResultDto.fromPrimitives({
            "user": userEntity.toPrimitives() if userEntity else {},
            "apiToken": apiToken,
        })

    def _failIfWrongInput(self) -> None:
        """
        @throws FieldsException
        """
        userFieldsValidator = UserFieldsValidator.getInstance()
        errors = [
            *userFieldsValidator.getErrors(self._createUserDto.getUserName(), self._createUserDto.getUserEmail()),
            *userFieldsValidator.getPasswordErrors(self._createUserDto.getPassword()),
        ]
        if not errors:
            return

        FieldsException.badRequestCustom(
            self._errorStackTrait.getErrorStackMessage(
                __(SharedMessageEnum.FIELDS_VALIDATION_ERRORS), errors
            ))

    def _failIfUserNameOrEmailExists(self) -> None:
        """
        @throws UsersException
        """
        userName = self._createUserDto.getUserName()
        if self._usersReaderSqliteRepository.doesUserNameExist(userName):
            UsersException.conflictCustom(__(UsersMessageEnum.USER_NAME_X_ALREADY_EXISTS, userName))

        userEmail = self._createUserDto.getUserEmail()
        if self._usersReaderSqliteRepository.doesUserEmailExist(userEmail):
            UsersException.conflictCustom(__(UsersMessageEnum.EMAIL_X_ALREADY_EXISTS, userEmail))
