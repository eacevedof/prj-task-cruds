-- 0000-create-migrations-table.sql
-- Control de migraciones ejecutadas (RunMigrationsService). Se lanza solo si la tabla no existe.

CREATE TABLE IF NOT EXISTS migrations
(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    migration_file VARCHAR(350) NOT NULL UNIQUE,
    batch INTEGER NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- SELECT * FROM migrations;
