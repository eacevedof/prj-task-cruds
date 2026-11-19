import re
import sqlite3
from abc import ABC

from Modules.Shared.Infrastructure.Factories.DbFactory import DbFactory
from Modules.Shared.Infrastructure.Traits.LogTrait import LogTrait


class AbstractSqliteRepository(ABC):
    """
    Base de los repositorios SQLite. Los metodos query, execute, executeRaw, lastInsertId y doesTableExist son para
    los hijos (protected en PHP). Las filas salen como dict (columna => valor).
    """

    def __init__(self):
        self.pdo: sqlite3.Connection = DbFactory.getInstance().getSqlitePdoByEnvConfiguration()
        self.logTrait = LogTrait.getInstance()

        # el lastInsertId() de PDO: aqui se guarda el de la ultima sentencia de execute()
        self._lastInsertId = 0

    def query(self, sql: str, params: list | tuple = ()) -> list[dict]:
        """
        SELECT con parametros enlazados (?). Devuelve las filas como dict (columna => valor).
        """
        self.logTrait.logSql(self._getSqlForLog(sql, params))
        return [dict(row) for row in self.pdo.execute(sql, params).fetchall()]

    def execute(self, sql: str, params: list | tuple = ()) -> int:
        """
        INSERT/UPDATE/DELETE con parametros enlazados. Devuelve las filas afectadas.
        """
        self.logTrait.logSql(self._getSqlForLog(sql, params))
        cursor = self.pdo.execute(sql, params)
        self._lastInsertId = int(cursor.lastrowid or 0)
        return int(cursor.rowcount)

    def executeRaw(self, sql: str) -> None:
        """
        Varias sentencias de golpe (p.ej. un fichero .sql). Sin parametros: solo SQL de confianza.
        """
        self.logTrait.logSql(sql)
        self.pdo.executescript(sql)

    def lastInsertId(self) -> int:
        """
        El id de la ultima fila insertada por execute().
        """
        return self._lastInsertId

    def doesTableExist(self, tableName: str) -> bool:
        sql = """
        -- doesTableExist
        SELECT
            COUNT(*) AS n_rows
        FROM `sqlite_master`
        WHERE 1=1
        AND type = 'table'
        AND name = ?
        """
        rows = self.query(sql, [tableName])
        return bool(rows[0]["n_rows"]) if rows else False

    def _getSqlForLog(self, sql: str, params: list | tuple) -> str:
        """
        SOLO para el log: la query con cada parametro en su sitio (strings entre comillas, como PDO::quote), para
        copiarla de sql.sql y lanzarla tal cual. Nunca se ejecuta: la real va siempre con parametros enlazados.
        Asume que no hay "?" dentro de literales del SQL (no los hay en los repositorios).
        Los hashes de password (bcrypt $2y$..., argon2 $argon2...) salen como '***': no deben quedar en sql.sql.
        """
        if not params:
            return sql

        def getParamForLog(param) -> str:
            if param is None:
                return "NULL"
            if isinstance(param, bool):
                return "1" if param else "0"
            if isinstance(param, (int, float)):
                return str(param)
            if not isinstance(param, str):
                return "'<blob>'"
            if re.match(r"^\$(2[aby]|argon2)", param):
                return "'***'"
            return "'" + param.replace("'", "''") + "'"

        paramsForLog = [getParamForLog(param) for param in params]
        position = iter(range(len(paramsForLog)))

        def replaceMark(_match: re.Match) -> str:
            index = next(position, None)
            return paramsForLog[index] if index is not None else "?"

        return re.sub(r"\?", replaceMark, sql)
