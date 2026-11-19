import json
import sys
from abc import ABC, abstractmethod
from typing import NoReturn

from Core.config.global_ import __

from Console.Application.InterfaceConsole import InterfaceConsole

from Modules.Shared.Domain.Enums.ExceptionEnum import ExceptionEnum
from Modules.Shared.Domain.Enums.SharedMessageEnum import SharedMessageEnum
from Modules.Shared.Infrastructure.Traits.CliColorTrait import CliColorTrait
from Modules.Shared.Infrastructure.Traits.LogTrait import LogTrait
from Modules.Shared.Infrastructure.Traits.MailTrait import MailTrait

_EXIT_CODE_DOMAIN_ERROR = 1
_EXIT_CODE_INFRASTRUCTURE_ERROR = 2


class AbstractConsoleCommand(InterfaceConsole, ABC):
    """
    El AbstractHttpController de la consola (vault: CLI Commands as Controllers): lee --clave=valor, imprime el
    ResultDto en JSON por stdout (sin color: se puede pasar a jq) y los errores en rojo por stderr con su codigo de salida.
    0 ok · 1 error de dominio (lo que en HTTP es un 4xx) · 2 error de infraestructura (el 500, se loguea).
    Vuelca (flush) cada salida: el sys.exit() de los errores no la corta.
    Los atributos input, cliColorTrait, logTrait y mailTrait son para los hijos (protected en PHP).
    """

    def __init__(self, input: list[str]):
        # los argumentos que van tras el alias del comando
        self.input = input
        self.cliColorTrait = CliColorTrait.getInstance()
        self.logTrait = LogTrait.getInstance()
        self.mailTrait = MailTrait.getInstance()

    @abstractmethod
    def run(self) -> None:
        ...

    def getOptionValue(self, optionName: str) -> str:
        """
        --user_name="Ana Lopez" → "Ana Lopez". Si no se pasa, "" (el servicio decide si es obligatorio).
        """
        optionPrefix = f"--{optionName}="
        for argument in self.input:
            if argument.startswith(optionPrefix):
                return argument[len(optionPrefix):]
        return ""

    def printResult(self, result) -> None:
        sys.stdout.write(f"{json.dumps(result, indent=4, ensure_ascii=False)}\n")
        sys.stdout.flush()

    def exitWithDomainError(self, ex) -> NoReturn:
        self._printErrors(ex.getCode(), [str(ex)])
        sys.exit(_EXIT_CODE_DOMAIN_ERROR)

    def exitWithUnexpectedError(self, ex: BaseException, logTitle: str) -> NoReturn:
        """
        Como el 500 de la API: se loguea con el metodo que fallo y se avisa por email. A diferencia de la API, quien
        tiene la consola si ve el mensaje real (logDebugCli), como en los commands de app-anubis.
        """
        self.logTrait.logException(ex, logTitle)
        self.logTrait.logDebugCli(str(ex), logTitle)
        self.mailTrait.sendMailOnThrowable(ex, logTitle)
        self._printErrors(
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
            [__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)],
        )
        sys.exit(_EXIT_CODE_INFRASTRUCTURE_ERROR)

    def _printErrors(self, code: int, errors: list[str]) -> None:
        """
        code: el mismo que daria la API (404, 409, 400...), para que consola y HTTP se lean igual
        """
        sys.stdout.flush()
        sys.stderr.write(f"{self.cliColorTrait.getRed(f'Error ({code}):')}\n")
        for error in errors:
            sys.stderr.write(f"{self.cliColorTrait.getRed(f'  - {error}')}\n")
        sys.stderr.flush()
