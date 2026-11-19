from Modules.Shared.Domain.Enums.ExceptionEnum import ExceptionEnum
from Modules.Shared.Domain.Enums.HttpEnum import HttpEnum


class HttpResponder:
    """
    Value object de TRANSPORTE HTTP: lo que un controller devuelve y el front controller (IndexMain) entrega a
    cmd/api/main.py para que lo emita. NINGUN controller escribe la respuesta: construye un HttpResponder y lo
    devuelve (asi los tests pueden asertar getStatusCode() sin emitir nada).
    """

    def __init__(self, statusCode: int, body: str, headers: dict):
        self._statusCode = statusCode
        self._body = body
        self._headers = headers

    @staticmethod
    def json(body: str, statusCode: int = ExceptionEnum.CODE_OK, headers: dict | None = None) -> "HttpResponder":
        """
        body es el JSON ya serializado.
        """
        return HttpResponder(
            statusCode,
            body,
            {HttpEnum.HEADER_CONTENT_TYPE: HttpEnum.CONTENT_TYPE_JSON, **(headers or {})},
        )

    @staticmethod
    def html(body: str, statusCode: int = ExceptionEnum.CODE_OK) -> "HttpResponder":
        return HttpResponder(statusCode, body, {HttpEnum.HEADER_CONTENT_TYPE: HttpEnum.CONTENT_TYPE_HTML})

    @staticmethod
    def noContent(statusCode: int = ExceptionEnum.CODE_NO_CONTENT) -> "HttpResponder":
        return HttpResponder(statusCode, "", {})

    def getStatusCode(self) -> int:
        return self._statusCode

    def getBody(self) -> str:
        return self._body

    def getHeaders(self) -> dict:
        return self._headers

    def getWithHeaders(self, headers: dict) -> "HttpResponder":
        """
        Copia con cabeceras comunes (CORS, seguridad) que pone el front controller; si coinciden, gana la del
        controller.
        """
        return HttpResponder(self._statusCode, self._body, {**headers, **self._headers})

    def echoHeadersAndBody(self, requestHandler) -> None:
        """
        UNICO punto de emision de toda la app. Lo llama cmd/api/main.py con lo que devuelve IndexMain.
        requestHandler es el http.server.BaseHTTPRequestHandler de la peticion.
        """
        body = self._body.encode("utf-8")
        requestHandler.send_response(self._statusCode)
        for key, value in self._headers.items():
            requestHandler.send_header(key, value)
        requestHandler.send_header("Content-Length", str(len(body)))
        requestHandler.end_headers()
        requestHandler.wfile.write(body)
