import re
from typing import Callable, NotRequired, TypedDict
from urllib.parse import unquote_plus

from Modules.Shared.Infrastructure.Components.HttpResponder.HttpResponder import HttpResponder


class Route(TypedDict):
    """
    Una entrada de src/Core/routes: lo que ejecuta la ruta recibe los tags de la url (":nombre") como argumentos.
    allowed: metodos HTTP en minusculas (HEAD va implicito en GET).
    """

    url: str
    controller: Callable[[dict], HttpResponder]
    allowed: NotRequired[list[str]]


class RunData(TypedDict):
    """
    Lo que devuelve getRunData(): la ruta + sus "_args"; si la url casa pero el metodo no, solo "allowed" (405); si no
    casa ninguna url, None (404).
    """

    route: NotRequired[Route]
    _args: NotRequired[dict]
    allowed: NotRequired[list[str]]


class ApiRouter:
    """
    Router REST: casa por METODO + patron de url.
    - url con tags ":nombre" -> se pasan al controller como argumentos con nombre.
    - getRunData() devuelve la ruta + "_args"; si la url casa pero el metodo no -> {allowed: [...]}
      (405); si no casa ninguna url -> None (404).
    """

    def __init__(self, routes: list[Route]):
        self._routes = routes

    @staticmethod
    def getInstance(routes: list[Route]) -> "ApiRouter":
        return ApiRouter(routes)

    def getRunData(self, requestMethod: str, requestUri: str) -> RunData | None:
        requestMethod = requestMethod.lower()
        # RFC 9110: todo recurso que admite GET admite HEAD
        if requestMethod == "head":
            requestMethod = "get"
        path = self._getPathWithoutQueryString(requestUri)

        allowedForPath: list[str] = []
        for route in self._routes:
            args = self._getArgsIfPathMatches(route["url"], path)
            if args is None:
                continue

            allowed = route.get("allowed", ["get"])
            if requestMethod in allowed:
                return {"route": route, "_args": args}
            allowedForPath.extend(allowed)

        # dict.fromkeys: sin repetidos y en orden de aparicion (el new Set de JS)
        return {"allowed": list(dict.fromkeys(allowedForPath))} if allowedForPath else None

    def _getPathWithoutQueryString(self, requestUri: str) -> str:
        path = re.split(r"[?#]", requestUri)[0].rstrip("/")
        return path or "/"

    def _getArgsIfPathMatches(self, routeUrl: str, path: str) -> dict | None:
        escapedRouteUrl = re.escape(routeUrl)
        # re.escape no toca ":" (3.7+): el tag ":nombre" sigue siendo ":nombre"
        pattern = re.sub(r":([a-zA-Z_][a-zA-Z0-9_]*)", r"(?P<\1>[^/]+)", escapedRouteUrl)
        matches = re.fullmatch(pattern, path)
        if matches is None:
            return None

        args: dict = {}
        for key, value in matches.groupdict().items():
            args[key] = self._getUrlDecoded(value).strip()
        return args

    def _getUrlDecoded(self, value: str) -> str:
        """
        urldecode(): "+" es un espacio y una secuencia % mal formada se queda tal cual.
        """
        try:
            return unquote_plus(value, errors="strict")
        except UnicodeDecodeError:
            return value
