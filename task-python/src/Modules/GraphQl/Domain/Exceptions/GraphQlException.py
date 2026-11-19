from typing import NoReturn

from Modules.Shared.Domain.Enums.ExceptionEnum import ExceptionEnum
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import InterfaceException

from Modules.GraphQl.Domain.Enums.GraphQlEnum import GraphQlEnum


class GraphQlException(Exception, InterfaceException):
    """
    Lo que lanza un resolver para que su mensaje llegue al cliente: graphql-core la envuelve en un GraphQLError
    (located_error) con su mismo mensaje y, como la guarda en original_error, copia su atributo `extensions` (el
    ProvidesExtensions de webonyx): su codigo en extensions.code y su mensaje en extensions.errors. graphql-core no
    oculta mensajes, asi que no hace falta el ClientAware de webonyx.
    """

    def __init__(self, message: str, code: int):
        super().__init__(message)
        self._code = code

    @staticmethod
    def clientAwareExceptionForWebonyx(ex: InterfaceException) -> NoReturn:
        """
        Excepcion controlada (de dominio o FieldsException): su codigo 4xx y su mensaje pasan tal cual al cliente.
        @throws GraphQlException
        """
        raise GraphQlException(str(ex), ex.getCode())

    @staticmethod
    def internalServerError(message: str) -> NoReturn:
        """
        Fallo no controlado: 500 con un mensaje generico, el original no sale (el controller ya lo ha logueado).
        @throws GraphQlException
        """
        raise GraphQlException(message, ExceptionEnum.CODE_INTERNAL_SERVER_ERROR)

    def getCode(self) -> int:
        return self._code

    def getExtensions(self) -> dict:
        return {
            GraphQlEnum.EXTENSION_CODE: self._code,
            GraphQlEnum.EXTENSION_ERRORS: [str(self)],
        }

    @property
    def extensions(self) -> dict:
        """
        Lo que lee graphql-core del original_error al montar el GraphQLError de la respuesta.
        """
        return self.getExtensions()
