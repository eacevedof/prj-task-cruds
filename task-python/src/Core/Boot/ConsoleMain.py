import json
import sys

from Core.Boot.Sapi import setSapiNameCli
from Core.config.global_ import lgerr

from Modules.Shared.Infrastructure.Components.Uuid.UuidGenerator import UuidGenerator
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import (
    getThrowableFileAndLine,
    getThrowableTraceAsString,
)
from Modules.Shared.Infrastructure.Repositories.Configuration.EnvironmentReaderRawRepository import EnvironmentReaderRawRepository




class ConsoleMain:
    """
    El console.php de task-php: python cmd/console/main.py <alias> --clave=valor ... -> el command del alias.
    Sin el modo --class=... de task-php (invocar cualquier clase por reflexion): alli nadie lo usa.
    """

    def __init__(self, argv: list[str] | None = None):
        """
        argv: el $argv de PHP, con el script en la posicion 0 y el alias en la 1
        """
        setSapiNameCli()
        self._argv = argv if argv is not None else []

    @staticmethod
    def getInstance(argv: list[str] | None = None) -> "ConsoleMain":
        return ConsoleMain(argv)

    @staticmethod
    def onError(ex: BaseException) -> None:
        uuid = UuidGenerator.getInstance().getUniqueIdWithPrefix("")
        file, line = getThrowableFileAndLine(ex)
        lgerr(str(ex), f"console-exception {uuid}", "error")
        lgerr(getThrowableTraceAsString(ex), f"console-exception {uuid} TRACE", "error")
        lgerr(f"{file} : (line: {line})", f"file-line {uuid}", "error")

    @staticmethod
    def debug(ex: BaseException) -> None:
        environmentReaderRawRepository = EnvironmentReaderRawRepository.getInstance()
        if environmentReaderRawRepository.isProduction():
            return

        if not environmentReaderRawRepository.isDebug():
            return

        file, line = getThrowableFileAndLine(ex)
        sys.stdout.write(json.dumps({
            "Exception": str(ex),
            "File": f"{file}({line})",
            "response": 500,
        }, indent=4, ensure_ascii=False) + "\n")
        sys.stdout.flush()

    def exec(self) -> None:
        """
        @throws Exception si el alias no tiene command
        """
        self._invokeConsoleServiceWithArgs()

    def _invokeConsoleServiceWithArgs(self) -> None:
        # import tardio: console.commands importa los commands de los modulos, que importan Shared (como en Node, no
        # hay ciclo; se hace aqui para que un fallo de import de un command solo afecte a la consola)
        from console.commands import getCommands

        alias = (self._argv[1] if len(self._argv) > 1 else "").strip()
        if not alias:
            alias = "help"

        commandConfig = getCommands().get(alias)
        if commandConfig is None:
            raise Exception(f"no class found for cmd {alias}")

        # a partir de la pos 2 en adelante son parametros de input
        inputArgs = self._argv[2:]
        commandConfig["service"](inputArgs).run()
