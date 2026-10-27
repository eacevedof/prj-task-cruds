package testcase;

import java.io.IOException;
import java.io.UncheckedIOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.List;

import core.boot.BootPathsEnum;
import core.config.Global;

import modules.shared.infrastructure.factories.DbFactory;
import modules.shared.infrastructure.repositories.FixturesRawRepositoryMock;

/**
 * El primer paso de cada clase de test que toca la BD (@BeforeAll): mismo arranque que la API pero contra una BD
 * SQLite PROPIA, que se borra y se recrea (migraciones + fixtures) en cada ejecucion. La BD de desarrollo no se toca.
 * Una BD por clase de test (testDbName): maven surefire lanza todas las clases en una JVM, una detras de otra, y cada
 * una fija su propio entorno, como task-go por paquete y task-rust por binario.
 */
public final class Bootstrap {

    private Bootstrap() {
    }

    public static void runWithTestDb(String testDbName) {
        String pathSqliteTest = BootPathsEnum.PATH_ROOT + "/database/task_cruds_test-" + testDbName + ".sqlite";
        loadTestEnv(pathSqliteTest);

        DbFactory.getInstance().closeAll();
        try {
            for (String suffix : List.of("", "-journal", "-wal", "-shm")) {
                Files.deleteIfExists(Path.of(pathSqliteTest + suffix));
            }
        }
        catch (IOException e) {
            throw new UncheckedIOException(e);
        }
        FixturesRawRepositoryMock.getInstance().createDbWithFixtures();
    }

    /**
     * El primer paso de las clases de test que no tocan la BD.
     */
    public static void runWithoutDb() {
        loadTestEnv(BootPathsEnum.PATH_ROOT + "/database/task_cruds_test-nodb.sqlite");
    }

    /**
     * putenv() gana sobre el .env (appboot_loadenv), asi que esta ruta manda.
     * APP_EMAIL_TO vacio: los tests que provocan un 500 no mandan emails de alerta.
     */
    private static void loadTestEnv(String pathSqliteTest) {
        Global.putenv("APP_SQLITE_PATH", pathSqliteTest);
        Global.putenv("APP_ENV", "test");
        Global.putenv("APP_EMAIL_TO", "");
        Global.putenv("APP_DOMAIN", "localhost");
        Global.appboot_loadenv();
    }
}
