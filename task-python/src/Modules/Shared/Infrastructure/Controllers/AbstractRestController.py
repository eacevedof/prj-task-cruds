import json

from Core.config.global_ import __

from Modules.Shared.Domain.Enums.ExceptionEnum import ExceptionEnum
from Modules.Shared.Domain.Enums.HttpEnum import HttpEnum
from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Components.HttpResponder.HttpResponder import HttpResponder
from Modules.Shared.Infrastructure.Controllers.AbstractHttpController import AbstractHttpController


class AbstractRestController(AbstractHttpController):
    """
    Base de los controllers REST. Envelope UNICO de respuesta, con el codigo tambien como status HTTP:
      {"code": int, "status": bool, "errors": string[], "data": object|array}
    """

    def respondOk(self, data, code: int = ExceptionEnum.CODE_OK) -> HttpResponder:
        return self._getJsonResponse(code, True, [], data)

    def respondErrors(self, code: int, errors: list[str]) -> HttpResponder:
        return self._getJsonResponse(code, False, errors, [])

    def respondDomainError(self, ex) -> HttpResponder:
        """
        Excepcion controlada (de dominio o FieldsException): su codigo 4xx y su mensaje viajan al cliente tal cual
        (una FieldsException ya trae en el mensaje el detalle por campo). El controller la distingue por su clase.
        """
        return self.respondErrors(ex.getCode(), [str(ex)])

    def respondUnexpectedError(self, ex: BaseException, logTitle: str) -> HttpResponder:
        """
        Cualquier otra cosa: 500, se loguea con el metodo que fallo y no filtra su mensaje.
        """
        self.logTrait.logException(ex, logTitle)
        self.mailTrait.sendMailOnThrowable(ex, logTitle)
        return self.respondErrors(
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
            [__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)],
        )

    def _getJsonResponse(self, code: int, status: bool, errors: list[str], data) -> HttpResponder:
        return HttpResponder.json(
            json.dumps(
                {
                    HttpEnum.KEY_CODE: code,
                    HttpEnum.KEY_STATUS: status,
                    HttpEnum.KEY_ERRORS: errors,
                    HttpEnum.KEY_DATA: data,
                },
                separators=(",", ":"),
                ensure_ascii=False,
            ),
            code,
            # RFC 6750: un 401 indica al cliente el esquema que se espera
            {HttpEnum.HEADER_WWW_AUTHENTICATE: HttpEnum.AUTH_SCHEME_BEARER}
            if code == ExceptionEnum.CODE_UNAUTHORIZED
            else {},
        )
