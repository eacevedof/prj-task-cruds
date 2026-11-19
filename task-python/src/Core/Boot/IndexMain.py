import json

from Core.Boot.ApiRouter import ApiRouter, Route
from Core.Boot.SensitiveDataMasker import SensitiveDataMasker
from Core.config.global_ import lgerr
from Core.routes.routes import getRoutes

from Modules.Shared.Domain.Enums.ExceptionEnum import ExceptionEnum
from Modules.Shared.Infrastructure.Components.HttpRequester.HttpRequester import HttpRequester
from Modules.Shared.Infrastructure.Components.HttpResponder.HttpResponder import HttpResponder
from Modules.Shared.Infrastructure.Components.Uuid.UuidGenerator import UuidGenerator
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import (
    getThrowableFileAndLine,
    getThrowableTraceAsString,
)
from Modules.Shared.Infrastructure.Repositories.Configuration.EnvironmentReaderRawRepository import EnvironmentReaderRawRepository


def _jsonDumps(value) -> str:
    # como JSON.stringify de Node: sin espacios y UTF-8 tal cual
    return json.dumps(value, ensure_ascii=False, separators=(",", ":"))


class IndexMain:
    """
    Front controller de la API (el public/index.php de task-php). Sin sesion ni vistas: solo JSON.
    Devuelve la respuesta en vez de emitirla: la emite cmd/api/main.py a partir de sus getters.
    """

    def __init__(self):
        self._httpRequest = HttpRequester.getInstance()
        self._routes: list[Route] = getRoutes()

    @staticmethod
    def getInstance() -> "IndexMain":
        return IndexMain()

    def exec(self) -> HttpResponder:
        requestMethod = self._httpRequest.getRequestMethod()
        if requestMethod == "options":
            return IndexMain._getWithCommonHeaders(HttpResponder.noContent())

        runData = ApiRouter.getInstance(self._routes).getRunData(
            requestMethod,
            self._httpRequest.getRequestUri()
        )

        if runData is None:
            return IndexMain._getWithCommonHeaders(self._getErrorResponse(
                ExceptionEnum.CODE_NOT_FOUND,
                "Resource not found"
            ))

        if "route" not in runData:
            return IndexMain._getWithCommonHeaders(self._getErrorResponse(
                ExceptionEnum.CODE_METHOD_NOT_ALLOWED,
                "Method not allowed",
                {"Allow": ", ".join(runData.get("allowed", [])).upper()}
            ))

        self._httpRequest.loadJsonBodyIntoPost()

        httpResponse = runData["route"]["controller"](runData.get("_args", {}))
        return IndexMain._getWithCommonHeaders(httpResponse)

    @staticmethod
    def onError(ex: BaseException) -> HttpResponder:
        """
        El error que nadie ha capturado acaba aqui: se loguea con un id y el cliente recibe ese id en un 500 generico.
        """
        uuid = UuidGenerator.getInstance().getUniqueIdWithPrefix("")
        sensitiveDataMasker = SensitiveDataMasker.getInstance()
        httpRequest = HttpRequester.getInstance()
        post = httpRequest.getPost()
        if len(post):
            lgerr(sensitiveDataMasker.getMaskedArray(post), f"index-exception {uuid} POST", "error")
        get = httpRequest.getGet()
        if len(get):
            lgerr(sensitiveDataMasker.getMaskedArray(get), f"index-exception {uuid} GET", "error")
        file, line = getThrowableFileAndLine(ex)
        lgerr(str(ex), f"index-exception {uuid}", "error")
        lgerr(getThrowableTraceAsString(ex), f"index-exception {uuid} TRACE", "error")
        lgerr(f"{file} : (line: {line})", f"file-line {uuid}", "error")

        return IndexMain._getWithCommonHeaders(HttpResponder.json(
            _jsonDumps({
                "code": ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
                "status": False,
                "errors": [f"Sorry! but some unexpected error occurred ({uuid})"],
                "data": [],
            }),
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
        ))

    @staticmethod
    def debug(ex: BaseException) -> None:
        environmentReaderRawRepository = EnvironmentReaderRawRepository.getInstance()
        if environmentReaderRawRepository.isProduction():
            return
        if not environmentReaderRawRepository.isDebug():
            return

        file, line = getThrowableFileAndLine(ex)
        lgerr({
            "Exception": str(ex),
            "File": f"{file}({line})",
            "POST": HttpRequester.getInstance().getPost(),
            "GET": HttpRequester.getInstance().getGet(),
        }, "index-debug", "debug")

    @staticmethod
    def _getWithCommonHeaders(httpResponse: HttpResponder) -> HttpResponder:
        """
        Toda respuesta sale por aqui: las cabeceras comunes se suman a las del HttpResponder y se emiten juntas
        por cmd/api/main.py, el unico punto que escribe la respuesta.
        """
        return httpResponse.getWithHeaders({
            **IndexMain._getCorsHeaders(),
            **IndexMain._getSecurityHeaders(),
        })

    @staticmethod
    def _getCorsHeaders() -> dict:
        httpRequest = HttpRequester.getInstance()
        corsHeaders: dict = {}
        origin = httpRequest.getHeaderValueByKey("Origin")
        if origin:
            corsHeaders["Access-Control-Allow-Origin"] = origin
            corsHeaders["Access-Control-Max-Age"] = "86400"
        if httpRequest.getRequestMethod() == "options":
            corsHeaders["Access-Control-Allow-Methods"] = "GET, POST, PUT, DELETE, OPTIONS"
            corsHeaders["Access-Control-Allow-Headers"] = "Content-Type, Accept, Accept-Language, Authorization, X-Time-Zone"
        return corsHeaders

    @staticmethod
    def _getSecurityHeaders() -> dict:
        return {
            "X-Content-Type-Options": "nosniff",
            "Referrer-Policy": "no-referrer",
        }

    def _getErrorResponse(self, code: int, error: str, headers: dict | None = None) -> HttpResponder:
        return HttpResponder.json(
            _jsonDumps({
                "code": code,
                "status": False,
                "errors": [error],
                "data": [],
            }),
            code,
            headers or {}
        )
