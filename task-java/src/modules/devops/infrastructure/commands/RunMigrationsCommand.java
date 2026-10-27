package modules.devops.infrastructure.commands;

import java.util.List;

import modules.devops.application.runmigrations.RunMigrationsDto;
import modules.devops.application.runmigrations.RunMigrationsResultDto;
import modules.devops.application.runmigrations.RunMigrationsService;
import modules.shared.application.Primitives;
import modules.shared.infrastructure.commands.AbstractConsoleCommand;

/**
 * bin/console run-migrations   (make migrate)
 * Traza start / mensajes / end con logDebugCli (pantalla con color + debug.log), como los commands de app-anubis.
 * Si una migracion falla sale con codigo 2: el entrypoint del contenedor (set -e) no arranca la API
 * contra una BD a medio migrar. El detalle del fallo queda en storage/logs (error.log).
 */
public final class RunMigrationsCommand extends AbstractConsoleCommand {

    private static final String LOG_TITLE = "RunMigrationsCommand::run";

    public RunMigrationsCommand(List<String> input) {
        super(input);
    }

    @Override
    public void run() {
        logTrait.logDebugCli("start", LOG_TITLE);
        try {
            RunMigrationsResultDto runMigrationsResultDto = RunMigrationsService.getInstance().invoke(
                RunMigrationsDto.fromPrimitives(Primitives.of())
            );
            for (String message : runMigrationsResultDto.getMessages()) {
                logTrait.logDebugCli(message, LOG_TITLE);
            }
        }
        catch (Exception e) {
            exitWithUnexpectedError(e, LOG_TITLE);
        }
        logTrait.logDebugCli("end", LOG_TITLE);
    }
}
