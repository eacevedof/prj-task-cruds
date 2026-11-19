import sys


class CliColorTrait:
    """
    Colores ANSI para la consola (copia de app-plataformabase): getXxx() devuelve el texto coloreado para
    componer una linea, echoXxx() la imprime entera por stdout. Sin dieRed: el codigo de salida lo pone
    AbstractConsoleCommand (1 dominio, 2 infraestructura).
    La salida se vuelca (flush) en cada linea: un sys.exit() justo despues no la corta.
    """

    @staticmethod
    def getInstance() -> "CliColorTrait":
        return CliColorTrait()

    def echoRed(self, text: str) -> None:
        self._echo(self.getRed(text))

    def getRed(self, text: str) -> str:
        return f"\x1b[91m{text}\x1b[0m"

    def echoGreen(self, text: str) -> None:
        self._echo(self.getGreen(text))

    def getGreen(self, text: str) -> str:
        return f"\x1b[92m{text}\x1b[0m"

    def echoYellow(self, text: str) -> None:
        self._echo(self.getYellow(text))

    def getYellow(self, text: str) -> str:
        return f"\x1b[93m{text}\x1b[0m"

    def echoBlue(self, text: str) -> None:
        self._echo(self.getBlue(text))

    def getBlue(self, text: str) -> str:
        return f"\x1b[94m{text}\x1b[0m"

    def echoWhite(self, text: str) -> None:
        self._echo(self.getWhite(text))

    def getWhite(self, text: str) -> str:
        return f"\x1b[97m{text}\x1b[0m"

    def echoOrange(self, text: str) -> None:
        self._echo(self.getOrange(text))

    def getOrange(self, text: str) -> str:
        return f"\x1b[38;5;214m{text}\x1b[0m"

    def _echo(self, line: str) -> None:
        sys.stdout.write(f"{line}\n")
        sys.stdout.flush()
