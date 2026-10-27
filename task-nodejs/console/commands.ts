/*
node cmd/console/main.ts <command-alias> --clave=valor .. --clave=valor
Ejemplo:
    node cmd/console/main.ts help

mapping:
    <command-alias> => {
        service: (input) => el command,
        info:    que hace (una linea),
        example: los argumentos de un ejemplo; el help lo imprime como "node cmd/console/main.ts <alias> <example>"
    }
*/
import type { InterfaceConsole } from "#app/Console/Application/InterfaceConsole.ts";
import { HelpCommand } from "#app/Console/Application/HelpCommand.ts";
import { RunMigrationsCommand } from "#app/Modules/Devops/Infrastructure/Commands/RunMigrationsCommand.ts";
import { CreateUserCommand } from "#app/Modules/Users/Infrastructure/Commands/CreateUserCommand.ts";
import { DeleteUserCommand } from "#app/Modules/Users/Infrastructure/Commands/DeleteUserCommand.ts";
import { GetUserCommand } from "#app/Modules/Users/Infrastructure/Commands/GetUserCommand.ts";
import { RegenerateUserApiTokenCommand } from "#app/Modules/Users/Infrastructure/Commands/RegenerateUserApiTokenCommand.ts";
import { UpdateUserCommand } from "#app/Modules/Users/Infrastructure/Commands/UpdateUserCommand.ts";

export type CommandConfig = {
    service: (input: string[]) => InterfaceConsole;
    info: string;
    example: string;
};

export function getCommands(): Record<string, CommandConfig> {
    return {
        "help": {
            service: (input) => new HelpCommand(input),
            info: "lista los comandos con un ejemplo de cada uno",
            example: "",
        },

        "run-migrations": {
            service: (input) => new RunMigrationsCommand(input),
            info: "ejecuta los .sql pendientes de database/migrations (tabla migrations)",
            example: "",
        },

        // cuenta (users): mismos casos de uso que la API; quien tiene la consola actua como el dueño de la cuenta
        "user-create": {
            service: (input) => new CreateUserCommand(input),
            info: "alta de cuenta; imprime su api_token (solo esta vez)",
            example: "--user_name=ana.lopez --user_email=ana@example.com --password=<password>",
        },
        "user-get": {
            service: (input) => new GetUserCommand(input),
            info: "detalle de una cuenta",
            example: "--user_uuid=<uuid>",
        },
        "user-update": {
            service: (input) => new UpdateUserCommand(input),
            info: "reemplazo completo de nombre y email (los dos obligatorios)",
            example: "--user_uuid=<uuid> --user_name=ana.lopez --user_email=ana@example.com",
        },
        "user-delete": {
            service: (input) => new DeleteUserCommand(input),
            info: "borrado fisico de la cuenta y sus tareas, sin confirmacion",
            example: "--user_uuid=<uuid>",
        },
        "user-regenerate-api-token": {
            service: (input) => new RegenerateUserApiTokenCommand(input),
            info: "nuevo api_token; el anterior deja de valer",
            example: "--user_uuid=<uuid>",
        },
    };
}
