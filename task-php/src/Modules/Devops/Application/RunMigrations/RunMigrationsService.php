<?php
declare(strict_types=1);

namespace App\Modules\Devops\Application\RunMigrations;

use App\Core\Application\Services\AbstractAppService;
use App\Core\Boot\BootPathsEnum;

use App\Modules\Devops\Infrastructure\Repositories\RunMigrationsReaderSqliteRepository;
use App\Modules\Devops\Infrastructure\Repositories\RunMigrationsWriterSqliteRepository;

/**
 * Ejecuta, en orden de nombre, los .sql de database/migrations que no esten en la tabla migrations.
 * Todos los pendientes de una ejecucion comparten batch (max(batch) + 1).
 * php console.php run-migrations
 */
final class RunMigrationsService extends AbstractAppService
{
    private const string MIGRATIONS_TABLE = "migrations";
    private const string CREATE_MIGRATIONS_TABLE_FILE = "0000-create-migrations-table.sql";

    private RunMigrationsReaderSqliteRepository $runMigrationsReaderSqliteRepository;
    private RunMigrationsWriterSqliteRepository $runMigrationsWriterSqliteRepository;

    private array $sqlMigrationsFiles = [];
    private array $sqlFilesToBeRan = [];

    public function __construct()
    {
        $this->runMigrationsReaderSqliteRepository = RunMigrationsReaderSqliteRepository::getInstance();
        $this->runMigrationsWriterSqliteRepository = RunMigrationsWriterSqliteRepository::getInstance();
    }

    public static function getInstance(): self
    {
        return new self();
    }

    public function __invoke(RunMigrationsDto $runMigrationsDto): void
    {
        $this->input = $runMigrationsDto;

        $this->createMigrationsTableIfDoesNotExist();

        $this->loadMigrationsFiles();
        if (!$this->sqlMigrationsFiles) {
            $this->print("No migrations files found");
            return;
        }

        $this->loadFilesToBeRan();
        if (!$this->sqlFilesToBeRan) {
            $this->print("No migrations files to be ran");
            return;
        }

        $this->runMigrations();
    }

    private function createMigrationsTableIfDoesNotExist(): void
    {
        if ($this->runMigrationsReaderSqliteRepository->doesMigrationsTableExist(self::MIGRATIONS_TABLE)) {
            $this->print("migrations table wont be created, already exists");
            return;
        }
        $this->print("creating migrations table");
        $this->runMigrationsWriterSqliteRepository->runSqlFile(
            $this->getPathSqlFile(self::CREATE_MIGRATIONS_TABLE_FILE)
        );
    }

    private function loadMigrationsFiles(): void
    {
        if (!$sqlFiles = array_diff(scandir(BootPathsEnum::PATH_MIGRATIONS), [".", ".."]))
            return;

        $sqlFiles = array_filter(
            $sqlFiles,
            fn(string $file) => pathinfo($file, PATHINFO_EXTENSION) === "sql"
                && $file !== self::CREATE_MIGRATIONS_TABLE_FILE
        );
        if (!$sqlFiles)
            return;

        sort($sqlFiles);
        $this->sqlMigrationsFiles = array_values($sqlFiles);
    }

    private function loadFilesToBeRan(): void
    {
        $ranFilesNames = array_column(
            $this->runMigrationsReaderSqliteRepository->getRanMigrationsFiles(),
            "migration_file"
        );
        foreach ($this->sqlMigrationsFiles as $sqlFile) {
            if (in_array($sqlFile, $ranFilesNames, true))
                continue;
            $this->sqlFilesToBeRan[] = $sqlFile;
        }
    }

    private function runMigrations(): void
    {
        $batch = $this->runMigrationsReaderSqliteRepository->getMaxBatch() + 1;
        $this->print("running migrations batch: $batch");
        foreach ($this->sqlFilesToBeRan as $sqlFile) {
            $this->print("running migration: $sqlFile");
            $this->runMigrationsWriterSqliteRepository->runMigrationFileAndSave(
                $this->getPathSqlFile($sqlFile),
                $sqlFile,
                $batch
            );
            $this->print("saved migration: $sqlFile");
        }
    }

    private function getPathSqlFile(string $sqlFile): string
    {
        return BootPathsEnum::PATH_MIGRATIONS."/$sqlFile";
    }

    private function print(string $message): void
    {
        $this->logDebug($message);
        echo $message.PHP_EOL;
    }

}
