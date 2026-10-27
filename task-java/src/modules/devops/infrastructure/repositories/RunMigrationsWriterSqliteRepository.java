package modules.devops.infrastructure.repositories;

import java.io.IOException;
import java.io.UncheckedIOException;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.sql.SQLException;

import modules.shared.infrastructure.repositories.AbstractSqliteRepository;

public final class RunMigrationsWriterSqliteRepository extends AbstractSqliteRepository {

    public static RunMigrationsWriterSqliteRepository getInstance() {
        return new RunMigrationsWriterSqliteRepository();
    }

    /**
     * El fichero va entero: executeRaw() lo ejecuta sentencia a sentencia.
     */
    public void runSqlFile(String pathSqlFile) {
        String sql = getFileContentSql(pathSqlFile);
        if (sql.isEmpty()) return;
        executeRaw(sql);
    }

    /**
     * Fichero + registro en migrations en la MISMA transaccion: si el SQL falla no queda aplicado a
     * medias ni marcado como ejecutado. Ojo: en SQLite el DDL tambien es transaccional.
     * Sin try/catch (un repositorio no captura): si algo falla no se llega al commit, la excepcion sube
     * hasta el command y SQLite deshace la transaccion abierta al cerrarse la conexion (el command sale con 2).
     */
    public void runMigrationFileAndSave(String pathSqlFile, String migrationFile, int batch) {
        try {
            pdo.setAutoCommit(false);
            runSqlFile(pathSqlFile);
            saveMigration(migrationFile, batch);
            pdo.commit();
            pdo.setAutoCommit(true);
        }
        catch (SQLException e) {
            throw new IllegalStateException(e.getMessage(), e);
        }
    }

    private void saveMigration(String migrationFile, int batch) {
        String sql = """
            -- saveMigration
            INSERT INTO `migrations` (migration_file, batch)
            VALUES (?, %d)
            """.formatted(batch);
        execute(sql, migrationFile);
    }

    private String getFileContentSql(String pathSqlFile) {
        try {
            return Files.readString(Path.of(pathSqlFile), StandardCharsets.UTF_8).trim();
        }
        catch (IOException e) {
            throw new UncheckedIOException(e);
        }
    }
}
