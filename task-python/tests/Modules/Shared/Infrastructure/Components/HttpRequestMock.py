import json

from Modules.Shared.Infrastructure.Components.HttpRequester.HttpRequestContext import HttpRequestContext


class HttpRequestMock:
    """
    Peticion simulada: cabeceras e ip (el Bearer que lee getAuthUserOrFail, User-Agent / Accept-Language / X-Time-Zone
    que lee HttpRequester), el body JSON ya en post (lo que hace IndexMain), la query en get y los tags de la url
    (":userUuid") como argumentos del controller. getMockedInstance() la carga en HttpRequestContext (como PHP rellena
    sus superglobales) y devuelve esos argumentos.
    """

    def __init__(self):
        self._bearerToken = ""
        self._ipAddress = ""
        self._headers: dict = {}
        self._routeParams: dict = {}
        self._query: dict = {}
        self._postRaw: dict = {}

    @staticmethod
    def getInstance() -> "HttpRequestMock":
        return HttpRequestMock()

    def setBearerToken(self, bearerToken: str) -> "HttpRequestMock":
        self._bearerToken = bearerToken
        return self

    def setIpAddress(self, ipAddress: str) -> "HttpRequestMock":
        self._ipAddress = ipAddress
        return self

    def setHeaders(self, headers: dict) -> "HttpRequestMock":
        """
        Cabeceras con su nombre HTTP: {"User-Agent": "...", "Accept-Language": "es-ES"}.
        """
        self._headers = headers
        return self

    def setRouteParams(self, routeParams: dict) -> "HttpRequestMock":
        self._routeParams = routeParams
        return self

    def setGetParams(self, queryParams: dict) -> "HttpRequestMock":
        self._query = queryParams
        return self

    def setPostRawParams(self, postParams: dict) -> "HttpRequestMock":
        self._postRaw = postParams
        return self

    def getMockedInstance(self) -> dict:
        """
        @returns los argumentos con nombre del controller
        """
        headers = {"content-type": "application/json"}
        if self._bearerToken:
            headers["authorization"] = f"Bearer {self._bearerToken}"
        for headerName, headerValue in self._headers.items():
            headers[headerName.lower()] = headerValue

        HttpRequestContext.getInstance().loadHttpRequest({
            "method": "POST",
            "requestUri": "/",
            "headers": headers,
            "body": json.dumps(self._postRaw),
            "remoteAddr": self._ipAddress,
            "get": self._query,
            "post": self._postRaw,
        })
        return self._routeParams

    def resetAll(self) -> None:
        self._bearerToken = ""
        self._ipAddress = ""
        self._headers = {}
        self._routeParams = {}
        self._query = {}
        self._postRaw = {}
