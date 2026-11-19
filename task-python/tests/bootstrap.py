import os

from Core.config.global_ import appboot_loadenv
from Core.Boot.BootPathsEnum import BootPathsEnum

from tests.Modules.Shared.Infrastructure.Repositories.FixturesRawRepositoryMock import FixturesRawRepositoryMock


def runWithTestDb(testDbName: str) -> None:
    """
    El primer paso de cada clase de test que toca la BD (setUpClass): mismo arranque que la API pero contra una BD
    SQLite PROPIA, que se borra y se recrea (migraciones + fixtures) en cada ejecucion. La BD de desarrollo no se toca.
    Una BD por clase de test (testDbName): unittest corre todos los ficheros en un solo proceso, asi que la ruta se
    fija aqui y no al importar (el import de todos los ficheros ocurre antes de ejecutar ninguno).
    """
    pathSqliteTest = f"{BootPathsEnum.PATH_ROOT}/database/task_cruds_test-{testDbName}.sqlite"
    _loadTestEnv(pathSqliteTest)

    for suffix in ["", "-journal", "-wal", "-shm"]:
        try:
            os.remove(f"{pathSqliteTest}{suffix}")
        except FileNotFoundError:
            pass
    FixturesRawRepositoryMock.getInstance().createDbWithFixtures()


def runWithoutDb() -> None:
    """
    El primer paso de las clases de test que no tocan la BD.
    """
    _loadTestEnv(f"{BootPathsEnum.PATH_ROOT}/database/task_cruds_test-nodb.sqlite")


def _loadTestEnv(pathSqliteTest: str) -> None:
    """
    La variable REAL gana sobre el .env (appboot_loadenv), asi que esta ruta manda.
    APP_EMAIL_TO vacio: los tests que provocan un 500 no mandan emails de alerta.
    """
    testEnv = {
        "APP_SQLITE_PATH": pathSqliteTest,
        "APP_ENV": "test",
        "APP_EMAIL_TO": "",
        "APP_DOMAIN": "localhost",
    }
    for key, value in testEnv.items():
        os.environ[key] = value
    appboot_loadenv()
