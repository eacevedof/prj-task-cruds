import sqlite3
import threading

from Modules.Shared.Infrastructure.Repositories.Configuration.EnvironmentReaderRawRepository import (
    EnvironmentReaderRawRepository,
)

_BUSY_TIMEOUT_SECONDS = 5.0

# una conexion por (hilo, fichero): sqlite3 no comparte conexion entre hilos (en PHP, un PDO por repositorio y peticion)
_sqliteDbs: dict[tuple[int, str], sqlite3.Connection] = {}
_lock = threading.Lock()


class DbFactory:

    @staticmethod
    def getInstance() -> "DbFactory":
        return DbFactory()

    def getSqlitePdoByEnvConfiguration(self) -> sqlite3.Connection:
        """
        La BD del fichero de APP_SQLITE_PATH. Si el fichero no existe, SQLite lo crea (lo normal en el primer
        make migrate). La conexion va en autocommit (isolation_level=None, como PDO) y con foreign_keys = ON (el
        ON DELETE CASCADE de tasks lo necesita). Una conexion por hilo, con busy_timeout de 5 s: dos peticiones
        a la vez esperan el bloqueo en vez de fallar.
        @throws RuntimeError
        """
        pathSqlite = EnvironmentReaderRawRepository.getInstance().getSqlitePath()
        if not pathSqlite:
            raise RuntimeError("missing env APP_SQLITE_PATH")

        key = (threading.get_ident(), pathSqlite)
        with _lock:
            sqliteDb = _sqliteDbs.get(key)
            if sqliteDb is not None:
                return sqliteDb

            # check_same_thread=False solo para que closeAll() pueda cerrarlas desde otro hilo: se usan en el suyo
            newSqliteDb = sqlite3.connect(
                pathSqlite, timeout=_BUSY_TIMEOUT_SECONDS, isolation_level=None, check_same_thread=False
            )
            newSqliteDb.row_factory = sqlite3.Row
            newSqliteDb.execute("PRAGMA foreign_keys = ON")
            _sqliteDbs[key] = newSqliteDb
            return newSqliteDb

    def closeAll(self) -> None:
        """
        Cierra las conexiones abiertas (los tests, antes de borrar el fichero de su BD).
        """
        with _lock:
            for sqliteDb in _sqliteDbs.values():
                sqliteDb.close()
            _sqliteDbs.clear()
