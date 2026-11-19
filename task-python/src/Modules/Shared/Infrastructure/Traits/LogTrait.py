import pprint
import sys
from datetime import datetime, timezone

from Core.Boot.BootPathsEnum import BootPathsEnum
from Core.Boot.Sapi import getSapiName, SAPI_CLI

from Modules.Shared.Infrastructure.Components.HttpRequester.HttpRequester import HttpRequester
from Modules.Shared.Infrastructure.Components.Logger.Logger import Logger
from Modules.Shared.Infrastructure.Components.Server.ServerIdentifier import ServerIdentifier
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import (
    getThrowableFileAndLine,
    getThrowableTraceAsString,
)


class LogTrait:
    """
    Logs de la app en storage/logs/YYYY-MM-DD-<nivel>.log (de app-anubis, Shared/Components/Traits/LogTrait).
    Cada entrada lleva la ip de quien la provoca (cliente HTTP, o "cli-<ip>" desde consola) y su nivel.
    Sin el envio a Elastic del original: aqui no hay modulo Elastic.
    """

    @staticmethod
    def getInstance() -> "LogTrait":
        return LogTrait()

    def logDebug(self, mixed, title: str = "") -> None:
        self._writeLevelLog("debug", "DEBUG", mixed, title)

    def logInfo(self, mixed, title: str = "") -> None:
        self._writeLevelLog("info", "INFO", mixed, title)

    def logSql(self, sql: str, title: str = "") -> None:
        self._writeLevelLog("sql", "SQL", sql, title)

    def logWarning(self, mixed, title: str = "") -> None:
        self._writeLevelLog("warning", "WARNING", mixed, title)

    def logError(self, mixed, title: str = "") -> None:
        self._writeLevelLog("error", "ERROR", mixed, title)

    def logException(self, throwable: BaseException, title: str = "ERROR") -> None:
        """
        Lo que va en todo catch: fichero, linea, codigo, mensaje y traza.
        """
        file, line = getThrowableFileAndLine(throwable)
        getCode = getattr(throwable, "getCode", None)
        code = getCode() if callable(getCode) else 0
        self._writeLevelLog("error", "ERROR", "\n".join([
            f"ex file:\n\t{file}",
            f"ex line:\n\t{line}",
            f"ex code:\n\t{code}",
            f"ex message:\n\t{throwable}",
            f"ex trace:\n\t{getThrowableTraceAsString(throwable)}",
        ]), title)

    def logDebugCli(self, mixed, title: str = "") -> None:
        """
        Solo en consola: al debug.log y ademas a pantalla con color (fecha, titulo, contenido).
        """
        if getSapiName() != SAPI_CLI:
            return

        now = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S")
        printable = self._getPrintable(mixed)
        Logger.getInstance().writeLog("debug", f"[{now}][{title}]\n{printable}", BootPathsEnum.PATH_LOGS)
        sys.stdout.write(f"\x1b[97m[{now}]\x1b[0m\x1b[93m[{title}]\x1b[0m\n\x1b[92m{printable}\x1b[0m\n")
        sys.stdout.flush()

    def _writeLevelLog(self, fileName: str, level: str, mixed, title: str) -> None:
        # en task-php es ["host_ip" => ip] y el implode se queda solo con el valor
        content = [self._getRemoteClientIP()]
        if title:
            content.append(title)
        content.append(self._getPrintable(mixed))

        Logger.getInstance().writeLog(fileName, f"[{level}] {chr(10).join(content)}", BootPathsEnum.PATH_LOGS)

    def _getRemoteClientIP(self) -> str:
        """
        La misma ip que llega a los dtos (HttpRequester.getClientIp).
        """
        if getSapiName() == SAPI_CLI:
            return f"cli-{ServerIdentifier.getInstance().getServerIp()}"

        return HttpRequester.getInstance().getClientIp() or "unknown"

    def _getPrintable(self, mixed) -> str:
        """
        print_r($mixed, true): un string tal cual, el resto legible.
        """
        return mixed if isinstance(mixed, str) else pprint.pformat(mixed, width=120)
