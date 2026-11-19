import posixpath
from pathlib import Path
from urllib.parse import unquote
import re

from Core.Boot.BootPathsEnum import BootPathsEnum

# tipos de los ficheros de public/ (los que hay)
CONTENT_TYPES = {
    ".css": "text/css; charset=utf-8",
    ".js": "text/javascript; charset=utf-8",
    ".html": "text/html; charset=utf-8",
    ".json": "application/json",
    ".txt": "text/plain; charset=utf-8",
    ".svg": "image/svg+xml",
    ".png": "image/png",
    ".jpg": "image/jpeg",
    ".ico": "image/vnd.microsoft.icon",
    ".woff2": "font/woff2",
}


class PublicFilesServer:
    """
    Lo que en task-php hace nginx: si la url es un fichero de public/ se sirve tal cual; si no, pasa al front
    controller (try_files $uri @rewriteapp). Los ficheros ocultos (.env, .git...) nunca: 404.
    """

    @staticmethod
    def getInstance() -> "PublicFilesServer":
        return PublicFilesServer()

    def getPublicFileResponse(self, requestUri: str) -> tuple[int, dict, bytes] | None:
        """
        None si la peticion no es de public/ y la tiene que atender IndexMain; si es suya, (status, cabeceras, body).
        En Node recibe el ServerResponse y responde; aqui devuelve la respuesta y la emite cmd/api/main.py.
        """
        urlPath = re.split(r"[?#]", requestUri)[0]
        if "/." in urlPath:
            return 404, {}, b"404 page not found\n"

        pathFile = self._getPathPublicFile(urlPath)
        if not pathFile:
            return None

        contentType = CONTENT_TYPES.get(Path(pathFile).suffix.lower(), "application/octet-stream")
        return 200, {"Content-Type": contentType}, Path(pathFile).read_bytes()

    def _getPathPublicFile(self, urlPath: str) -> str:
        """
        La ruta del fichero dentro de public/, o "" si no existe, no es un fichero o se sale de public/.
        """
        try:
            relativePath = unquote(urlPath, errors="strict").lstrip("/")
        except UnicodeDecodeError:
            return ""
        if not relativePath or ".." in posixpath.normpath(relativePath.replace("\\", "/")).split("/"):
            return ""

        pathFile = f"{BootPathsEnum.PATH_PUBLIC}/{relativePath}"
        return pathFile if Path(pathFile).is_file() else ""
