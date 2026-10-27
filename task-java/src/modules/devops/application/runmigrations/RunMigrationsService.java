package modules.devops.application.runmigrations;

import java.io.IOException;
import java.io.UncheckedIOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.ArrayList;
import java.util.List;
import java.util.stream.Stream;

import core.boot.BootPathsEnum;

import modules.devops.infrastructure.repositories.RunMigrationsReaderSqliteRepository;
import modules.devops.infrastructure.repositories.RunMigrationsWriterSqliteRepository;
import modules.shared.application.Primitives;

/**
 * Ejecuta, en orden de nombre, los .sql de database/migrations que no esten en la tabla migrations.
 * Todos los pendientes de una ejecucion comparten batch (max(batch) + 1).
 * bin/console run-migrations
 */
public final class RunMigrationsService {

    private static final String MIGRATIONS_TABLE = "migrations";
    private static final String CREATE_MIGRATIONS_TABLE_FILE = "0000-create-migrations-table.sql";

    private final RunMigrationsReaderSqliteRepository runMigrationsReaderSqliteRepository;
    private final RunMigrationsWriterSqliteRepository runMigrationsWriterSqliteRepository;

    private List<String> sqlMigrationsFiles = new ArrayList<>();
    private final List<String> sqlFilesToBeRan = new ArrayList<>();
    private final List<String> messages = new ArrayList<>();

    private RunMigrationsService() {
        this.runMigrationsReaderSqliteRepository = RunMigrationsReaderSqliteRepository.getInstance();
        this.runMigrationsWriterSqliteRepository = RunMigrationsWriterSqliteRepository.getInstance();
    }

    public static RunMigrationsService getInstance() {
        return new RunMigrationsService();
    }

    /**
     * @throws IllegalStateException si una migracion falla
     */
    public RunMigrationsResultDto invoke(RunMigrationsDto runMigrationsDto) {
        createMigrationsTableIfDoesNotExist();

        loadMigrationsFiles();
        if (sqlMigrationsFiles.isEmpty()) {
            addMessage("No migrations files found");
            return RunMigrationsResultDto.fromPrimitives(Primitives.of("messages", messages));
        }

        loadFilesToBeRan();
        if (sqlFilesToBeRan.isEmpty()) {
            addMessage("No migrations files to be ran");
            return RunMigrationsResultDto.fromPrimitives(Primitives.of("messages", messages));
        }

        runMigrations();
        return RunMigrationsResultDto.fromPrimitives(Primitives.of("messages", messages));
    }

    private void createMigrationsTableIfDoesNotExist() {
        if (runMigrationsReaderSqliteRepository.doesMigrationsTableExist(MIGRATIONS_TABLE)) {
            addMessage("migrations table wont be created, already exists");
            return;
        }
        addMessage("creating migrations table");
        runMigrationsWriterSqliteRepository.runSqlFile(getPathSqlFile(CREATE_MIGRATIONS_TABLE_FILE));
    }

    private void loadMigrationsFiles() {
        try (Stream<Path> paths = Files.list(Path.of(BootPathsEnum.PATH_MIGRATIONS))) {
            List<String> sqlFiles = paths
                .map(path -> path.getFileName().toString())
                .filter(file -> file.endsWith(".sql") && !file.equals(CREATE_MIGRATIONS_TABLE_FILE))
                .sorted()
                .toList();
            if (sqlFiles.isEmpty())
                return;

            sqlMigrationsFiles = sqlFiles;
        }
        catch (IOException e) {
            throw new UncheckedIOException(e);
        }
    }

    private void loadFilesToBeRan() {
        List<String> ranFilesNames = runMigrationsReaderSqliteRepository.getRanMigrationsFiles().stream()
            .map(ranMigrationFile -> String.valueOf(ranMigrationFile.get("migration_file")))
            .toList();
        for (String sqlFile : sqlMigrationsFiles) {
            if (ranFilesNames.contains(sqlFile))
                continue;
            sqlFilesToBeRan.add(sqlFile);
        }
    }

    private void runMigrations() {
        int batch = runMigrationsReaderSqliteRepository.getMaxBatch() + 1;
        addMessage("running migrations batch: " + batch);
        for (String sqlFile : sqlFilesToBeRan) {
            addMessage("running migration: " + sqlFile);
            runMigrationsWriterSqliteRepository.runMigrationFileAndSave(getPathSqlFile(sqlFile), sqlFile, batch);
            addMessage("saved migration: " + sqlFile);
        }
    }

    private String getPathSqlFile(String sqlFile) {
        return BootPathsEnum.PATH_MIGRATIONS + "/" + sqlFile;
    }

    /**
     * Traza para el ResultDto: el command la imprime y la deja en el log de debug.
     */
    private void addMessage(String message) {
        messages.add(message);
    }
}
