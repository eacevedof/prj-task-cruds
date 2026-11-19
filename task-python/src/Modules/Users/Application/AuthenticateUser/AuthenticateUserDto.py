from Modules.Shared.Application.AbstractInputDto import AbstractInputDto
from Modules.Shared.Application.Primitives import Primitives, getTrimmedString


class AuthenticateUserDto(AbstractInputDto):

    def __init__(self, primitives: Primitives):
        super().__init__(primitives)

        self._apiToken = getTrimmedString(primitives.get("apiToken"))

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "AuthenticateUserDto":
        return AuthenticateUserDto(primitives)

    def getApiToken(self) -> str:
        return self._apiToken
