"""
Consola de task-python: el console/console.php de task-php. python cmd/console/main.py help
"""
import sys
from pathlib import Path

# el proyecto fuera de docker (PYTHONPATH ya lo trae el contenedor): src/ para Core/Modules/Console y la raiz para console/
_PATH_ROOT = Path(__file__).resolve().parents[2]
for _path in (_PATH_ROOT / "src", _PATH_ROOT):
    if str(_path) not in sys.path:
        sys.path.insert(0, str(_path))

from Core.Boot.ConsoleMain import ConsoleMain  # noqa: E402
from Core.config.global_ import appboot_loadenv  # noqa: E402

from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable  # noqa: E402


def main() -> None:
    try:
        appboot_loadenv()
    except Exception as e:
        print(getThrowable(e), file=sys.stderr)
        sys.exit(2)

    try:
        # sys.argv ya es el $argv de PHP: el script en la 0 y luego el alias
        ConsoleMain.getInstance(sys.argv).exec()
    except Exception as e:
        throwable = getThrowable(e)
        ConsoleMain.debug(throwable)
        ConsoleMain.onError(throwable)


if __name__ == "__main__":
    main()
