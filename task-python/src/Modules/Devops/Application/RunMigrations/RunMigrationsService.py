import os

from Core.Boot.BootPathsEnum import BootPathsEnum

from Modules.Devops.Application.RunMigrations.RunMigrationsDto import RunMigrationsDto
from Modules.Devops.Application.RunMigrations.RunMigrationsResultDto import RunMigrationsResultDto
from Modules.Devops.Infrastructure.Repositories.RunMigrationsReaderSqliteRepository import RunMigrationsReaderSqliteRepository
from Modules.Devops.Infrastructure.Repositories.RunMigrationsWriterSqliteRepository import RunMigrationsWriterSqliteRepository

MIGRATIONS_TABLE = "migrations"
CREATE_MIGRATIONS_TABLE_FILE = "0000-create-migrations-table.sql"


class RunMigrationsService:
    """
    Ejecuta, en orden de nombre, los .sql de database/migrations que no esten en la tabla migrations.
    Todos los pendientes de una ejecucion comparten batch (max(batch) + 1).
    python cmd/console/main.py run-migrations
    """

    def __init__(self):
        self._runMigrationsReaderSqliteRepository = RunMigrationsReaderSqliteRepository.getInstance()
        self._runMigrationsWriterSqliteRepository = RunMigrationsWriterSqliteRepository.getInstance()

        self._sqlMigrationsFiles: list[str] = []
        self._sqlFilesToBeRan: list[str] = []
        self._messages: list[str] = []

    @staticmethod
    def getInstance() -> "RunMigrationsService":
        return RunMigrationsService()

    def invoke(self, _runMigrationsDto: RunMigrationsDto) -> RunMigrationsResultDto:
        self._createMigrationsTableIfDoesNotExist()

        self._loadMigrationsFiles()
        if not self._sqlMigrationsFiles:
            self._addMessage("No migrations files found")
            return RunMigrationsResultDto.fromPrimitives({"messages": self._messages})

        self._loadFilesToBeRan()
        if not self._sqlFilesToBeRan:
            self._addMessage("No migrations files to be ran")
            return RunMigrationsResultDto.fromPrimitives({"messages": self._messages})

        self._runMigrations()
        return RunMigrationsResultDto.fromPrimitives({"messages": self._messages})

    def _createMigrationsTableIfDoesNotExist(self) -> None:
        if self._runMigrationsReaderSqliteRepository.doesMigrationsTableExist(MIGRATIONS_TABLE):
            self._addMessage("migrations table wont be created, already exists")
            return
        self._addMessage("creating migrations table")
        self._runMigrationsWriterSqliteRepository.runSqlFile(
            self._getPathSqlFile(CREATE_MIGRATIONS_TABLE_FILE)
        )

    def _loadMigrationsFiles(self) -> None:
        sqlFiles = [
            file for file in os.listdir(BootPathsEnum.PATH_MIGRATIONS)
            if os.path.splitext(file)[1] == ".sql" and file != CREATE_MIGRATIONS_TABLE_FILE
        ]
        if not sqlFiles:
            return

        self._sqlMigrationsFiles = sorted(sqlFiles)

    def _loadFilesToBeRan(self) -> None:
        ranFilesNames = [
            ranMigrationFile["migration_file"]
            for ranMigrationFile in self._runMigrationsReaderSqliteRepository.getRanMigrationsFiles()
        ]
        for sqlFile in self._sqlMigrationsFiles:
            if sqlFile in ranFilesNames:
                continue
            self._sqlFilesToBeRan.append(sqlFile)

    def _runMigrations(self) -> None:
        batch = self._runMigrationsReaderSqliteRepository.getMaxBatch() + 1
        self._addMessage(f"running migrations batch: {batch}")
        for sqlFile in self._sqlFilesToBeRan:
            self._addMessage(f"running migration: {sqlFile}")
            self._runMigrationsWriterSqliteRepository.runMigrationFileAndSave(
                self._getPathSqlFile(sqlFile),
                sqlFile,
                batch
            )
            self._addMessage(f"saved migration: {sqlFile}")

    def _getPathSqlFile(self, sqlFile: str) -> str:
        return f"{BootPathsEnum.PATH_MIGRATIONS}/{sqlFile}"

    def _addMessage(self, message: str) -> None:
        """
        Traza para el ResultDto: el command la imprime y la deja en el log de debug.
        """
        self._messages.append(message)
