from contextvars import ContextVar
from typing import Callable, TypedDict, TypeVar

T = TypeVar("T")


class HttpRequest(TypedDict):
    """
    La peticion HTTP en curso: lo que en PHP son las superglobales ($_SERVER, $_GET, $_POST). Las cabeceras van en
    minusculas. post empieza vacio: lo rellena HttpRequester.loadJsonBodyIntoPost().
    Es un dict normal: method, requestUri, headers, body, remoteAddr, get, post.
    """

    method: str
    requestUri: str
    headers: dict
    body: str
    remoteAddr: str
    get: dict
    post: dict


# una peticion por contexto (cada hilo del servidor tiene el suyo): dos peticiones en paralelo no se ven entre si
_httpRequestVar: ContextVar = ContextVar("httpRequest", default=None)


class HttpRequestContext:
    """
    Donde vive la peticion en curso para que HttpRequester.getInstance() la lea sin que nadie se la pase, como lee
    PHP sus superglobales. Fuera de una peticion (consola, un test sin peticion) devuelve una vacia.
    """

    @staticmethod
    def getInstance() -> "HttpRequestContext":
        return HttpRequestContext()

    def runWithHttpRequest(self, httpRequest: HttpRequest, callback: Callable[[], T]) -> T:
        """
        Lo que se ejecuta en callback (la peticion entera) ve httpRequest.
        """
        token = _httpRequestVar.set(httpRequest)
        try:
            return callback()
        finally:
            _httpRequestVar.reset(token)

    def loadHttpRequest(self, httpRequest: HttpRequest) -> None:
        """
        Desde aqui hasta el final del contexto actual se ve httpRequest: lo usan los tests (HttpRequestMock), que
        cargan la peticion simulada como en PHP se rellenan las superglobales.
        """
        _httpRequestVar.set(httpRequest)

    def getHttpRequest(self) -> HttpRequest:
        httpRequest = _httpRequestVar.get()
        return httpRequest if httpRequest is not None else self.getEmptyHttpRequest()

    def getEmptyHttpRequest(self) -> HttpRequest:
        return {
            "method": "",
            "requestUri": "",
            "headers": {},
            "body": "",
            "remoteAddr": "",
            "get": {},
            "post": {},
        }
