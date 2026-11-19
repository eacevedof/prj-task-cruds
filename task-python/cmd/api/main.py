"""
API de task-python: el public/index.php de task-php. python cmd/api/main.py --addr=0.0.0.0:8080
Cada peticion se atiende entera dentro de su HttpRequestContext (un ContextVar por hilo): lo que en PHP son las
superglobales de esa peticion.
"""
import sys
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import parse_qsl, urlsplit

# el proyecto fuera de docker (PYTHONPATH ya lo trae el contenedor): src/ para Core/Modules/Console y la raiz para console/
_PATH_ROOT = Path(__file__).resolve().parents[2]
for _path in (_PATH_ROOT / "src", _PATH_ROOT):
    if str(_path) not in sys.path:
        sys.path.insert(0, str(_path))

from Core.Boot.IndexMain import IndexMain  # noqa: E402
from Core.Boot.PublicFilesServer import PublicFilesServer  # noqa: E402
from Core.config.global_ import appboot_loadenv  # noqa: E402

from Modules.Shared.Infrastructure.Components.HttpRequester.HttpRequestContext import HttpRequestContext  # noqa: E402
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable  # noqa: E402

DEFAULT_LISTEN_ADDRESS = "0.0.0.0:8080"
# el body mas grande que se lee (el post_max_size de PHP): uno mayor llega vacio
MAX_BODY_BYTES = 8 * 1024 * 1024


def getListenAddress() -> str:
    """
    --addr=host:puerto (por defecto 0.0.0.0:8080)
    """
    for argument in sys.argv[1:]:
        if argument.startswith("--addr="):
            return argument[len("--addr="):]
    return DEFAULT_LISTEN_ADDRESS


class ApiRequestHandler(BaseHTTPRequestHandler):
    # keep-alive: cada respuesta lleva su Content-Length
    protocol_version = "HTTP/1.1"
    server_version = "task-python"

    def do_GET(self) -> None:
        self._serveRequest()

    do_POST = do_PUT = do_PATCH = do_DELETE = do_OPTIONS = do_HEAD = do_GET

    def log_message(self, format: str, *args) -> None:
        # sin log de acceso en stderr: la API loguea en storage/logs, como las demas
        pass

    def _serveRequest(self) -> None:
        """
        Los ficheros de public/ los sirve PublicFilesServer (lo que hace nginx en task-php); el resto, IndexMain.
        """
        httpRequest = self._getHttpRequest()

        publicFileResponse = PublicFilesServer.getInstance().getPublicFileResponse(httpRequest["requestUri"])
        if publicFileResponse is not None:
            status, headers, body = publicFileResponse
            self._send(status, headers, body)
            return

        def exec():
            try:
                return IndexMain.getInstance().exec()
            except Exception as e:
                throwable = getThrowable(e)
                IndexMain.debug(throwable)
                return IndexMain.onError(throwable)

        httpResponder = HttpRequestContext.getInstance().runWithHttpRequest(httpRequest, exec)
        if self.command == "HEAD":
            self._send(
                httpResponder.getStatusCode(),
                httpResponder.getHeaders(),
                httpResponder.getBody().encode("utf-8"),
            )
            return
        httpResponder.echoHeadersAndBody(self)

    def _send(self, status: int, headers: dict, body: bytes) -> None:
        self.send_response(status)
        for key, value in headers.items():
            self.send_header(key, value)
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()
        if self.command != "HEAD":
            self.wfile.write(body)

    def _getHttpRequest(self) -> dict:
        """
        La peticion de http.server como HttpRequest: lo que en PHP son las superglobales.
        """
        requestUri = self.path or "/"
        headers: dict = {}
        for name in self.headers.keys():
            # una cabecera repetida llega unida por coma, como la da node:http
            headers[name.lower()] = ", ".join(self.headers.get_all(name) or [])

        return {
            "method": self.command or "GET",
            "requestUri": requestUri,
            "headers": headers,
            "body": self._getBody(),
            # una ipv4 por un socket ipv6 llega como "::ffff:172.18.0.1"
            "remoteAddr": self.client_address[0].removeprefix("::ffff:"),
            "get": dict(parse_qsl(urlsplit(requestUri).query, keep_blank_values=True)),
            "post": {},
        }

    def _getBody(self) -> str:
        try:
            contentLength = int(self.headers.get("Content-Length") or 0)
        except ValueError:
            contentLength = 0
        if contentLength <= 0:
            return ""
        if contentLength > MAX_BODY_BYTES:
            # se descarta en trozos (sin cargarlo en memoria) y llega vacio
            remaining = contentLength
            while remaining > 0:
                chunk = self.rfile.read(min(65536, remaining))
                if not chunk:
                    break
                remaining -= len(chunk)
            return ""
        return self.rfile.read(contentLength).decode("utf-8", errors="replace")


def main() -> None:
    try:
        appboot_loadenv()
    except Exception as e:
        print(getThrowable(e), file=sys.stderr)
        sys.exit(1)

    host, _, port = getListenAddress().rpartition(":")
    server = ThreadingHTTPServer((host, int(port)), ApiRequestHandler)
    server.daemon_threads = True
    print(f"task-python api listening on {host}:{port}", flush=True)
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass


if __name__ == "__main__":
    main()
