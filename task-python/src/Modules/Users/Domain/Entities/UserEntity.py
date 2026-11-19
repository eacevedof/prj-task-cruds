from Modules.Shared.Application.Primitives import Primitives, getInt, getTrimmedString

from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum


class UserEntity:

    USER_UUID_PREFIX = "user-"

    def __init__(self, primitives: Primitives):
        """
        Desde una fila de la tabla users (columnas en snake_case: contrato con la BD). Las lecturas no traen
        password_hash ni api_token_hash: solo los informan las escrituras.
        """
        rawId = primitives.get(UsersKeyEnum.ID)
        self._id: int | None = getInt(rawId) if rawId is not None else None
        self._userUuid = getTrimmedString(primitives.get(UsersKeyEnum.USER_UUID))
        self._userName = getTrimmedString(primitives.get(UsersKeyEnum.USER_NAME))
        self._userEmail = getTrimmedString(primitives.get(UsersKeyEnum.USER_EMAIL))
        self._passwordHash = getTrimmedString(primitives.get(UsersKeyEnum.PASSWORD_HASH))
        self._apiTokenHash = getTrimmedString(primitives.get(UsersKeyEnum.API_TOKEN_HASH))
        self._createdAt = getTrimmedString(primitives.get(UsersKeyEnum.CREATED_AT))
        self._updatedAt = getTrimmedString(primitives.get(UsersKeyEnum.UPDATED_AT))

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "UserEntity":
        return UserEntity(primitives)

    def toPrimitives(self) -> Primitives:
        """
        Representacion publica (API): el id interno no sale, el identificador es user_uuid. Los hashes nunca.
        """
        return {
            UsersKeyEnum.USER_UUID: self._userUuid,
            UsersKeyEnum.USER_NAME: self._userName,
            UsersKeyEnum.USER_EMAIL: self._userEmail,
            UsersKeyEnum.CREATED_AT: self._createdAt,
            UsersKeyEnum.UPDATED_AT: self._updatedAt,
        }

    def getId(self) -> int | None:
        return self._id

    def getUserUuid(self) -> str:
        return self._userUuid

    def getUserName(self) -> str:
        return self._userName

    def getUserEmail(self) -> str:
        return self._userEmail

    def getPasswordHash(self) -> str:
        return self._passwordHash

    def getApiTokenHash(self) -> str:
        return self._apiTokenHash

    def getCreatedAt(self) -> str:
        return self._createdAt

    def getUpdatedAt(self) -> str:
        return self._updatedAt
