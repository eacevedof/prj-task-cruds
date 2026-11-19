from Modules.Shared.Domain.Enums.ExceptionEnum import ExceptionEnum

from Modules.Shared.Infrastructure.Exceptions.InterfaceException import InterfaceException


class FieldsException(Exception, InterfaceException):

    def __init__(self, message: str):
        super().__init__(message)
        self.message = message
        self._code = ExceptionEnum.CODE_BAD_REQUEST

    @staticmethod
    def badRequestCustom(message: str):
        """
        @throws FieldsException
        """
        raise FieldsException(message)

    def getCode(self) -> int:
        return self._code
