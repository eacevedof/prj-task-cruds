import traceback
from abc import ABC, abstractmethod


class InterfaceException(ABC):
    """
    Lo que tiene toda excepcion controlada (de dominio o FieldsException): su mensaje (str(ex)) y un codigo HTTP 4xx.
    Es el Exception de PHP que reciben respondDomainError() y exitWithDomainError().
    """

    @abstractmethod
    def getCode(self) -> int:
        ...


def getThrowable(thrown) -> BaseException:
    """
    El Throwable de PHP: lo que llega a un catch como excepcion. Lo que se lanza sin ser una excepcion se envuelve.
    """
    return thrown if isinstance(thrown, BaseException) else Exception(str(thrown))


def getThrowableFileAndLine(throwable: BaseException) -> tuple[str, int]:
    """
    getFile() y getLine() de un Throwable: el frame mas interno de la traza (donde se lanzo).
    """
    frames = traceback.extract_tb(throwable.__traceback__)
    if not frames:
        return "", 0
    return frames[-1].filename, frames[-1].lineno or 0


def getThrowableTraceAsString(throwable: BaseException) -> str:
    """
    getTraceAsString(): una linea "at funcion (fichero:linea)" por frame, sin el mensaje.
    """
    frames = traceback.extract_tb(throwable.__traceback__)
    return "\n".join(f"at {frame.name} ({frame.filename}:{frame.lineno})" for frame in reversed(frames))
