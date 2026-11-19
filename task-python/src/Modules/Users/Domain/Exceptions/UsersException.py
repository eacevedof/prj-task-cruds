from typing import NoReturn

from Modules.Shared.Domain.Enums.ExceptionEnum import ExceptionEnum
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import InterfaceException


class UsersException(Exception, InterfaceException):

    def __init__(self, message: str, code: int):
        super().__init__(message)
        self._code = code

    @staticmethod
    def unauthorizedCustom(message: str) -> NoReturn:
        """
        @throws UsersException
        """
        raise UsersException(message, ExceptionEnum.CODE_UNAUTHORIZED)

    @staticmethod
    def notFoundCustom(message: str) -> NoReturn:
        """
        @throws UsersException
        """
        raise UsersException(message, ExceptionEnum.CODE_NOT_FOUND)

    @staticmethod
    def conflictCustom(message: str) -> NoReturn:
        """
        @throws UsersException
        """
        raise UsersException(message, ExceptionEnum.CODE_CONFLICT)

    def getCode(self) -> int:
        return self._code
