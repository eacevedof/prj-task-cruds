"""
Funciones globales de la app (el global.ts de Node). Se llama global_ porque `global` es palabra reservada de
Python y `from Core.config.global import __` no compila.
"""
import os
import pprint
import re

from Core.Boot.BootPathsEnum import BootPathsEnum

from Modules.Shared.Domain.Enums.AppEnvKeyEnum import AppEnvKeyEnum
from Modules.Shared.Infrastructure.Components.Logger.Logger import Logger


def appboot_loadenv() -> None:
    """
    Carga <root>/.env y FALLA RUIDOSO si tras cargarlo falta alguna clave obligatoria. Sin .env la app
    arrancaria degradada en silencio. Las vars REALES del contenedor/OS cuentan: sin fichero pero con las
    claves en el entorno, arranca.
    @throws RuntimeError
    """
    pathEnv = f"{BootPathsEnum.PATH_ROOT}/.env"
    appboot_loadenvfile(pathEnv)
    appboot_assertrequiredenvkeys(pathEnv)


def appboot_loadenvfile(pathEnv: str) -> None:
    if not os.path.exists(pathEnv):
        return

    replacements = {
        "%PATH_PUBLIC%": BootPathsEnum.PATH_PUBLIC,
        "%PATH_ROOT%": BootPathsEnum.PATH_ROOT,
        "%PATH_SRC%": BootPathsEnum.PATH_SRC,
        "%PATH_SRC_CONFIG%": BootPathsEnum.PATH_SRC_CONFIG,
    }

    with open(pathEnv, "r", encoding="utf-8") as envFile:
        lines = [line for line in re.split(r"\r?\n", envFile.read()) if line != ""]
    for line in lines:
        if line.strip().startswith("#") or "=" not in line:
            continue

        separatorIndex = line.index("=")
        name = line[:separatorIndex].strip()
        if name == "":
            continue

        # la variable de entorno REAL (contenedor/OS) gana sobre el fichero .env
        if name in os.environ:
            continue

        value = appboot_getenvfilevalue(line[separatorIndex + 1:])
        for placeholder, path in replacements.items():
            value = value.replace(placeholder, path)
        os.environ[name] = value


def appboot_getenvfilevalue(rawValue: str) -> str:
    """
    Valor tal cual lo escribiria dotenv: entre comillas ("x" o 'x') se toma el interior literal; sin comillas
    se corta el comentario en linea (" # ..." o un valor que empieza por "#").
    """
    value = rawValue.strip()
    matches = re.match(r"^([\"'])(.*)\1", value)
    if matches:
        return matches.group(2)
    return re.sub(r"(^|\s+)#.*$", "", value).strip()


def appboot_assertrequiredenvkeys(pathEnv: str) -> None:
    """
    @throws RuntimeError
    """
    missingKeys = appboot_getmissingenvkeys()
    if not missingKeys:
        return

    reason = (
        "the .env file lacks them"
        if os.path.exists(pathEnv)
        else f"missing file {pathEnv} (copy .env.example to .env)"
    )
    raise RuntimeError(f"appboot_loadenv: required env keys not set ({', '.join(missingKeys)}): {reason}")


def appboot_getmissingenvkeys() -> list[str]:
    """
    Claves obligatorias ausentes o vacias.
    """
    requiredKeys = [
        AppEnvKeyEnum.APP_ENV,
        AppEnvKeyEnum.APP_DOMAIN,
        AppEnvKeyEnum.APP_SQLITE_PATH,
    ]
    return [key for key in requiredKeys if os.environ.get(key, "").strip() == ""]


def lgerr(mixed, title: str = "", type: str = "error") -> None:
    """
    storage/logs/YYYY-MM-DD-<type>.log (mismo esquema que LogTrait/Logger).
    """
    content = mixed if isinstance(mixed, str) else pprint.pformat(mixed, width=120)
    Logger.getInstance().writeLog(
        f"{type}.log",
        f"(lgerr) <<  {title} >>\n{content}" if title else f"(lgerr)\n{content}",
        BootPathsEnum.PATH_LOGS,
    )


def __(msgId: str, *args) -> str:
    """
    El __() de task-php: sustituye {0}, {1}... por los args. El idioma de alli sale de $_REQUEST["lang"], que la API
    nunca rellena (y no hay src/Core/locale): siempre responde en ingles, el msgid tal cual. Aqui igual.
    """
    msgChanged = msgId.strip()
    if not msgChanged:
        return ""
    for index, arg in enumerate(args):
        msgChanged = msgChanged.replace("{" + str(index) + "}", str(arg))
    return msgChanged
