package commands

import (
	"task-go/src/modules/devops/application/runmigrations"
	sharedcommands "task-go/src/modules/shared/infrastructure/commands"
)

// RunMigrationsCommand ./console run-migrations   (make migrate)
// Traza start / mensajes / end con LogDebugCli (pantalla con color + debug.log).
// Si una migracion falla sale con codigo 2: el entrypoint del contenedor (set -e) no arranca la API contra una BD a
// medio migrar. El detalle del fallo queda en storage/logs (error.log).
type RunMigrationsCommand struct {
	sharedcommands.AbstractConsoleCommand
}

func RunMigrationsCommandGetInstance(input []string) *RunMigrationsCommand {
	return &RunMigrationsCommand{AbstractConsoleCommand: sharedcommands.NewAbstractConsoleCommand(input)}
}

func (c *RunMigrationsCommand) Run() {
	const logTitle = "RunMigrationsCommand.Run"
	c.LogDebugCli("start", logTitle)
	runMigrationsResultDto, err := runmigrations.RunMigrationsServiceGetInstance().Invoke(
		runmigrations.RunMigrationsDtoFromPrimitives(map[string]any{}),
	)
	if err != nil {
		c.ExitWithUnexpectedError(err, logTitle)
	}
	for _, message := range runMigrationsResultDto.GetMessages() {
		c.LogDebugCli(message, logTitle)
	}
	c.LogDebugCli("end", logTitle)
}
