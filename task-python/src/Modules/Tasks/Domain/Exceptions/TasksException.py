from typing import NoReturn

from Modules.Shared.Domain.Enums.ExceptionEnum import ExceptionEnum


class TasksException(Exception):

    def __init__(self, message: str, code: int):
        super().__init__(message)
        self._code = code

    @staticmethod
    def unauthorizedCustom(message: str) -> NoReturn:
        """
        @throws TasksException
        """
        raise TasksException(message, ExceptionEnum.CODE_UNAUTHORIZED)

    @staticmethod
    def notFoundCustom(message: str) -> NoReturn:
        """
        @throws TasksException
        """
        raise TasksException(message, ExceptionEnum.CODE_NOT_FOUND)

    def getCode(self) -> int:
        return self._code
