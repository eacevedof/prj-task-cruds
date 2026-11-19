"""
Equivalente a php_sapi_name(): "server" para la API y "cli" para la consola. Lo fija ConsoleMain al arrancar
(setSapiNameCli) y es de solo lectura despues.
"""

SAPI_SERVER = "server"
SAPI_CLI = "cli"

_sapiName = SAPI_SERVER


def setSapiNameCli() -> None:
    global _sapiName
    _sapiName = SAPI_CLI


def getSapiName() -> str:
    return _sapiName
