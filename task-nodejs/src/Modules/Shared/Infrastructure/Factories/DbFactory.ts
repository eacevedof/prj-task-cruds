import { DatabaseSync } from "node:sqlite";

import { EnvironmentReaderRawRepository } from "#app/Modules/Shared/Infrastructure/Repositories/Configuration/EnvironmentReaderRawRepository.ts";

// una conexion por fichero para todo el proceso (en PHP, un PDO por repositorio y peticion)
const sqliteDbsByPath = new Map<string, DatabaseSync>();

const BUSY_TIMEOUT_MS = 5000;

export class DbFactory {

    static getInstance(): DbFactory {
        return new DbFactory();
    }

    /**
     * La BD del fichero de APP_SQLITE_PATH. Si el fichero no existe, SQLite lo crea (lo normal en el primer
     * make migrate). La conexion lleva foreign_keys = ON (el ON DELETE CASCADE de tasks lo necesita). Una sola
     * conexion: node:sqlite es sincrono y Node atiende una sentencia cada vez, asi que nunca responde
     * "database is locked" por la propia API.
     * @throws Error
     */
    getSqlitePdoByEnvConfiguration(): DatabaseSync {
        const pathSqlite = EnvironmentReaderRawRepository.getInstance().getSqlitePath();
        if (!pathSqlite) {
            throw new Error("missing env APP_SQLITE_PATH");
        }

        const sqliteDb = sqliteDbsByPath.get(pathSqlite);
        if (sqliteDb) {
            return sqliteDb;
        }

        const newSqliteDb = new DatabaseSync(pathSqlite, { timeout: BUSY_TIMEOUT_MS });
        newSqliteDb.exec("PRAGMA foreign_keys = ON");
        sqliteDbsByPath.set(pathSqlite, newSqliteDb);
        return newSqliteDb;
    }

    /**
     * Cierra las conexiones abiertas (los tests, antes de borrar el fichero de su BD).
     */
    closeAll(): void {
        for (const sqliteDb of sqliteDbsByPath.values()) {
            sqliteDb.close();
        }
        sqliteDbsByPath.clear();
    }
}
