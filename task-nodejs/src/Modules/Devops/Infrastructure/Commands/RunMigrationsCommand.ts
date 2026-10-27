import { AbstractConsoleCommand } from "#app/Modules/Shared/Infrastructure/Commands/AbstractConsoleCommand.ts";
import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

import { RunMigrationsDto } from "#app/Modules/Devops/Application/RunMigrations/RunMigrationsDto.ts";
import { RunMigrationsService } from "#app/Modules/Devops/Application/RunMigrations/RunMigrationsService.ts";

const LOG_TITLE = "RunMigrationsCommand::run";

/**
 * node cmd/console/main.ts run-migrations   (make migrate)
 * Traza start / mensajes / end con logDebugCli (pantalla con color + debug.log), como los commands de app-anubis.
 * Si una migracion falla sale con codigo 2: el entrypoint del contenedor (set -e) no arranca la API
 * contra una BD a medio migrar. El detalle del fallo queda en storage/logs (error.log).
 */
export class RunMigrationsCommand extends AbstractConsoleCommand {

    run(): void {
        this.logTrait.logDebugCli("start", LOG_TITLE);
        try {
            const runMigrationsResultDto = RunMigrationsService.getInstance().invoke(RunMigrationsDto.fromPrimitives({}));
            for (const message of runMigrationsResultDto.getMessages()) {
                this.logTrait.logDebugCli(message, LOG_TITLE);
            }
        }
        catch (e) {
            this.exitWithUnexpectedError(getThrowable(e), LOG_TITLE);
        }
        this.logTrait.logDebugCli("end", LOG_TITLE);
    }
}
