from Modules.Shared.Infrastructure.Commands.AbstractConsoleCommand import AbstractConsoleCommand
from Modules.Shared.Infrastructure.Exceptions.InterfaceException import getThrowable

from Modules.Devops.Application.RunMigrations.RunMigrationsDto import RunMigrationsDto
from Modules.Devops.Application.RunMigrations.RunMigrationsService import RunMigrationsService

LOG_TITLE = "RunMigrationsCommand::run"


class RunMigrationsCommand(AbstractConsoleCommand):
    """
    python cmd/console/main.py run-migrations   (make migrate)
    Traza start / mensajes / end con logDebugCli (pantalla con color + debug.log), como los commands de app-anubis.
    Si una migracion falla sale con codigo 2: el entrypoint del contenedor (set -e) no arranca la API
    contra una BD a medio migrar. El detalle del fallo queda en storage/logs (error.log).
    """

    def run(self) -> None:
        self.logTrait.logDebugCli("start", LOG_TITLE)
        try:
            runMigrationsResultDto = RunMigrationsService.getInstance().invoke(RunMigrationsDto.fromPrimitives({}))
            for message in runMigrationsResultDto.getMessages():
                self.logTrait.logDebugCli(message, LOG_TITLE)
        except Exception as e:
            self.exitWithUnexpectedError(getThrowable(e), LOG_TITLE)
        self.logTrait.logDebugCli("end", LOG_TITLE)
