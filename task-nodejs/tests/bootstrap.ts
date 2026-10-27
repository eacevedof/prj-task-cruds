import { rmSync } from "node:fs";

import { BootPathsEnum } from "#app/Core/Boot/BootPathsEnum.ts";
import { appboot_loadenv } from "#app/Core/config/global.ts";

import { FixturesRawRepositoryMock } from "#tests/Modules/Shared/Infrastructure/Repositories/FixturesRawRepositoryMock.ts";

/**
 * El primer paso de cada fichero de test que toca la BD: mismo arranque que la API pero contra una BD SQLite PROPIA,
 * que se borra y se recrea (migraciones + fixtures) en cada ejecucion. La BD de desarrollo no se toca.
 * Una BD por fichero de test (testDbName): node --test lanza cada fichero en su proceso y en paralelo, como task-go
 * por paquete y task-rust por binario.
 */
export function runWithTestDb(testDbName: string): void {
    const pathSqliteTest = `${BootPathsEnum.PATH_ROOT}/database/task_cruds_test-${testDbName}.sqlite`;
    loadTestEnv(pathSqliteTest);

    for (const suffix of ["", "-journal", "-wal", "-shm"]) {
        rmSync(`${pathSqliteTest}${suffix}`, { force: true });
    }
    FixturesRawRepositoryMock.getInstance().createDbWithFixtures();
}

/**
 * El primer paso de los ficheros de test que no tocan la BD.
 */
export function runWithoutDb(): void {
    loadTestEnv(`${BootPathsEnum.PATH_ROOT}/database/task_cruds_test-nodb.sqlite`);
}

/**
 * La variable REAL gana sobre el .env (appboot_loadenv), asi que esta ruta manda.
 * APP_EMAIL_TO vacio: los tests que provocan un 500 no mandan emails de alerta.
 */
function loadTestEnv(pathSqliteTest: string): void {
    const testEnv: Record<string, string> = {
        APP_SQLITE_PATH: pathSqliteTest,
        APP_ENV: "test",
        APP_EMAIL_TO: "",
        APP_DOMAIN: "localhost",
    };
    for (const [key, value] of Object.entries(testEnv)) {
        process.env[key] = value;
    }
    appboot_loadenv();
}
