from Modules.Shared.Application.Primitives import Primitives, getTrimmedString

from Modules.Users.Domain.Enums.UsersKeyEnum import UsersKeyEnum


class RegenerateUserApiTokenResultDto:

    def __init__(self, userUuid: str, apiToken: str):
        self._userUuid = userUuid
        self._apiToken = apiToken

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "RegenerateUserApiTokenResultDto":
        return RegenerateUserApiTokenResultDto(
            getTrimmedString(primitives.get("userUuid")),
            getTrimmedString(primitives.get("apiToken")),
        )

    def getUserUuid(self) -> str:
        return self._userUuid

    def getApiToken(self) -> str:
        return self._apiToken

    def toArray(self) -> Primitives:
        return {
            UsersKeyEnum.USER_UUID: self._userUuid,
            UsersKeyEnum.API_TOKEN: self._apiToken,
        }
