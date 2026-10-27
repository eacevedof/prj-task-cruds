import { readFileSync } from "node:fs";

import { AbstractSqliteRepository } from "#app/Modules/Shared/Infrastructure/Repositories/AbstractSqliteRepository.ts";

export class RunMigrationsWriterSqliteRepository extends AbstractSqliteRepository {

    static getInstance(): RunMigrationsWriterSqliteRepository {
        return new RunMigrationsWriterSqliteRepository();
    }

    /**
     * SQLite ejecuta varias sentencias en un solo exec(): el fichero va entero, sin trocearlo por ";".
     */
    runSqlFile(pathSqlFile: string): void {
        const sql = this.#getFileContentSql(pathSqlFile);
        if (!sql) return;
        this.executeRaw(sql);
    }

    /**
     * Fichero + registro en migrations en la MISMA transaccion: si el SQL falla no queda aplicado a
     * medias ni marcado como ejecutado. Ojo: en SQLite el DDL tambien es transaccional.
     * Sin try/catch (un repositorio no captura): si algo falla no se llega al commit, la excepcion sube
     * hasta el command y SQLite deshace la transaccion abierta al cerrarse la conexion (el command sale con 2).
     */
    runMigrationFileAndSave(pathSqlFile: string, migrationFile: string, batch: number): void {
        this.pdo.exec("BEGIN");
        this.runSqlFile(pathSqlFile);
        this.#saveMigration(migrationFile, batch);
        this.pdo.exec("COMMIT");
    }

    #saveMigration(migrationFile: string, batch: number): void {
        const sql = `
        -- saveMigration
        INSERT INTO \`migrations\` (migration_file, batch)
        VALUES (?, ${batch})
        `;
        this.execute(sql, [migrationFile]);
    }

    #getFileContentSql(pathSqlFile: string): string {
        return readFileSync(pathSqlFile, "utf8").trim();
    }
}
