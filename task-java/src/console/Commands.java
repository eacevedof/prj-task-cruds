/*
bin/console <command-alias> --clave=valor .. --clave=valor
Ejemplo:
    bin/console help

mapping:
    <command-alias> => CommandConfig(
        service: (input) -> el command,
        info:    que hace (una linea),
        example: los argumentos de un ejemplo; el help lo imprime como "bin/console <alias> <example>"
    )
*/
package console;

import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.function.Function;

import console.application.HelpCommand;
import console.application.InterfaceConsole;

import modules.devops.infrastructure.commands.RunMigrationsCommand;
import modules.users.infrastructure.commands.CreateUserCommand;
import modules.users.infrastructure.commands.DeleteUserCommand;
import modules.users.infrastructure.commands.GetUserCommand;
import modules.users.infrastructure.commands.RegenerateUserApiTokenCommand;
import modules.users.infrastructure.commands.UpdateUserCommand;

public final class Commands {

    public record CommandConfig(Function<List<String>, InterfaceConsole> service, String info, String example) {
    }

    private Commands() {
    }

    public static Map<String, CommandConfig> getCommands() {
        Map<String, CommandConfig> commands = new LinkedHashMap<>();
        commands.put("help", new CommandConfig(
            HelpCommand::new,
            "lista los comandos con un ejemplo de cada uno",
            ""
        ));

        commands.put("run-migrations", new CommandConfig(
            RunMigrationsCommand::new,
            "ejecuta los .sql pendientes de database/migrations (tabla migrations)",
            ""
        ));

        // cuenta (users): mismos casos de uso que la API; quien tiene la consola actua como el dueño de la cuenta
        commands.put("user-create", new CommandConfig(
            CreateUserCommand::new,
            "alta de cuenta; imprime su api_token (solo esta vez)",
            "--user_name=ana.lopez --user_email=ana@example.com --password=<password>"
        ));
        commands.put("user-get", new CommandConfig(
            GetUserCommand::new,
            "detalle de una cuenta",
            "--user_uuid=<uuid>"
        ));
        commands.put("user-update", new CommandConfig(
            UpdateUserCommand::new,
            "reemplazo completo de nombre y email (los dos obligatorios)",
            "--user_uuid=<uuid> --user_name=ana.lopez --user_email=ana@example.com"
        ));
        commands.put("user-delete", new CommandConfig(
            DeleteUserCommand::new,
            "borrado fisico de la cuenta y sus tareas, sin confirmacion",
            "--user_uuid=<uuid>"
        ));
        commands.put("user-regenerate-api-token", new CommandConfig(
            RegenerateUserApiTokenCommand::new,
            "nuevo api_token; el anterior deja de valer",
            "--user_uuid=<uuid>"
        ));
        return commands;
    }
}
