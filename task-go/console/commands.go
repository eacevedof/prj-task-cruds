// Package console registro de comandos de consola (el console/commands.php de task-php).
//
//	./console <command-alias> --clave=valor .. --clave=valor
//	Ejemplo:
//	    ./console help
package console

import (
	"task-go/src/console/application"
	devopscommands "task-go/src/modules/devops/infrastructure/commands"
	userscommands "task-go/src/modules/users/infrastructure/commands"
)

// GetCommands alias => command, que hace (una linea) y los argumentos de un ejemplo. El orden es el del help.
func GetCommands() []application.CommandConfig {
	return []application.CommandConfig{
		{
			Alias: "help",
			Service: func(input []string) application.InterfaceConsole {
				return application.HelpCommandGetInstance(input, GetCommands())
			},
			Info:    "lista los comandos con un ejemplo de cada uno",
			Example: "",
		},

		{
			Alias: "run-migrations",
			Service: func(input []string) application.InterfaceConsole {
				return devopscommands.RunMigrationsCommandGetInstance(input)
			},
			Info:    "ejecuta los .sql pendientes de database/migrations (tabla migrations)",
			Example: "",
		},

		// cuenta (users): mismos casos de uso que la API; quien tiene la consola actua como el dueño de la cuenta
		{
			Alias: "user-create",
			Service: func(input []string) application.InterfaceConsole {
				return userscommands.CreateUserCommandGetInstance(input)
			},
			Info:    "alta de cuenta; imprime su api_token (solo esta vez)",
			Example: "--user_name=ana.lopez --user_email=ana@example.com --password=<password>",
		},
		{
			Alias: "user-get",
			Service: func(input []string) application.InterfaceConsole {
				return userscommands.GetUserCommandGetInstance(input)
			},
			Info:    "detalle de una cuenta",
			Example: "--user_uuid=<uuid>",
		},
		{
			Alias: "user-update",
			Service: func(input []string) application.InterfaceConsole {
				return userscommands.UpdateUserCommandGetInstance(input)
			},
			Info:    "reemplazo completo de nombre y email (los dos obligatorios)",
			Example: "--user_uuid=<uuid> --user_name=ana.lopez --user_email=ana@example.com",
		},
		{
			Alias: "user-delete",
			Service: func(input []string) application.InterfaceConsole {
				return userscommands.DeleteUserCommandGetInstance(input)
			},
			Info:    "borrado fisico de la cuenta y sus tareas, sin confirmacion",
			Example: "--user_uuid=<uuid>",
		},
		{
			Alias: "user-regenerate-api-token",
			Service: func(input []string) application.InterfaceConsole {
				return userscommands.RegenerateUserApiTokenCommandGetInstance(input)
			},
			Info:    "nuevo api_token; el anterior deja de valer",
			Example: "--user_uuid=<uuid>",
		},
	}
}
