package modules.devops.infrastructure.repositories;

import java.util.List;
import java.util.Map;

import modules.shared.infrastructure.repositories.AbstractSqliteRepository;

import static modules.shared.application.Primitives.getInt;

public final class RunMigrationsReaderSqliteRepository extends AbstractSqliteRepository {

    public static RunMigrationsReaderSqliteRepository getInstance() {
        return new RunMigrationsReaderSqliteRepository();
    }

    public boolean doesMigrationsTableExist(String tableName) {
        return doesTableExist(tableName);
    }

    /**
     * Filas {id, migration_file}.
     */
    public List<Map<String, Object>> getRanMigrationsFiles() {
        String sql = """
            -- getRanMigrationsFiles
            SELECT
                id, migration_file
            FROM `migrations`
            WHERE 1=1
            ORDER BY migration_file
            """;
        return query(sql);
    }

    public int getMaxBatch() {
        String sql = """
            -- getMaxBatch
            SELECT
                MAX(batch) AS max_batch
            FROM `migrations`
            WHERE 1=1
            """;
        List<Map<String, Object>> rows = query(sql);
        return rows.isEmpty() ? 0 : getInt(rows.getFirst().get("max_batch"));
    }
}
