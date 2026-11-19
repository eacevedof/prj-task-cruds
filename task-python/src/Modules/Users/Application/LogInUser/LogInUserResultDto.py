from Modules.Shared.Application.Primitives import Primitives, getPrimitives, getTrimmedString

from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum


class LogInUserResultDto:

    def __init__(self, primitives: Primitives):
        # el usuario (UserEntity.toPrimitives) + su api_token nuevo en claro, solo aqui
        self._user = getPrimitives(primitives.get("user"))
        self._apiToken = getTrimmedString(primitives.get("apiToken"))

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "LogInUserResultDto":
        return LogInUserResultDto(primitives)

    def getUser(self) -> Primitives:
        return self._user

    def getApiToken(self) -> str:
        return self._apiToken

    def toArray(self) -> Primitives:
        return {
            **self._user,
            UsersKeyEnum.API_TOKEN: self._apiToken,
        }
