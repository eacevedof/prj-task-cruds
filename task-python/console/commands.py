"""
python cmd/console/main.py <command-alias> --clave=valor .. --clave=valor
Ejemplo:
    python cmd/console/main.py help

mapping:
    <command-alias> => {
        service: (input) => el command,
        info:    que hace (una linea),
        example: los argumentos de un ejemplo; el help lo imprime como "python cmd/console/main.py <alias> <example>"
    }
"""
from typing import Callable, TypedDict

from Console.Application.InterfaceConsole import InterfaceConsole
from Console.Application.HelpCommand import HelpCommand
from Modules.Devops.Infrastructure.Commands.RunMigrationsCommand import RunMigrationsCommand
from Modules.Users.Infrastructure.Commands.CreateUserCommand import CreateUserCommand
from Modules.Users.Infrastructure.Commands.DeleteUserCommand import DeleteUserCommand
from Modules.Users.Infrastructure.Commands.GetUserCommand import GetUserCommand
from Modules.Users.Infrastructure.Commands.RegenerateUserApiTokenCommand import RegenerateUserApiTokenCommand
from Modules.Users.Infrastructure.Commands.UpdateUserCommand import UpdateUserCommand


class CommandConfig(TypedDict):
    service: Callable[[list[str]], InterfaceConsole]
    info: str
    example: str


def getCommands() -> dict[str, CommandConfig]:
    return {
        "help": {
            "service": lambda input: HelpCommand(input),
            "info": "lista los comandos con un ejemplo de cada uno",
            "example": "",
        },

        "run-migrations": {
            "service": lambda input: RunMigrationsCommand(input),
            "info": "ejecuta los .sql pendientes de database/migrations (tabla migrations)",
            "example": "",
        },

        # cuenta (users): mismos casos de uso que la API; quien tiene la consola actua como el dueño de la cuenta
        "user-create": {
            "service": lambda input: CreateUserCommand(input),
            "info": "alta de cuenta; imprime su api_token (solo esta vez)",
            "example": "--user_name=ana.lopez --user_email=ana@example.com --password=<password>",
        },
        "user-get": {
            "service": lambda input: GetUserCommand(input),
            "info": "detalle de una cuenta",
            "example": "--user_uuid=<uuid>",
        },
        "user-update": {
            "service": lambda input: UpdateUserCommand(input),
            "info": "reemplazo completo de nombre y email (los dos obligatorios)",
            "example": "--user_uuid=<uuid> --user_name=ana.lopez --user_email=ana@example.com",
        },
        "user-delete": {
            "service": lambda input: DeleteUserCommand(input),
            "info": "borrado fisico de la cuenta y sus tareas, sin confirmacion",
            "example": "--user_uuid=<uuid>",
        },
        "user-regenerate-api-token": {
            "service": lambda input: RegenerateUserApiTokenCommand(input),
            "info": "nuevo api_token; el anterior deja de valer",
            "example": "--user_uuid=<uuid>",
        },
    }
