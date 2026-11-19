from Modules.Shared.Application.AbstractInputDto import AbstractInputDto
from Modules.Shared.Application.Primitives import Primitives, getTrimmedString


class LogInUserDto(AbstractInputDto):

    def __init__(self, primitives: Primitives):
        super().__init__(primitives)

        self._userName = getTrimmedString(primitives.get("userName"))
        self._password = getTrimmedString(primitives.get("password"))

    @staticmethod
    def fromPrimitives(primitives: Primitives) -> "LogInUserDto":
        return LogInUserDto(primitives)

    def getUserName(self) -> str:
        return self._userName

    def getPassword(self) -> str:
        return self._password
