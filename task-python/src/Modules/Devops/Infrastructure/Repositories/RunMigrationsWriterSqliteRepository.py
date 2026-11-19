import sqlite3

from Modules.Shared.Infrastructure.Repositories.AbstractSqliteRepository import AbstractSqliteRepository


class RunMigrationsWriterSqliteRepository(AbstractSqliteRepository):

    @staticmethod
    def getInstance() -> "RunMigrationsWriterSqliteRepository":
        return RunMigrationsWriterSqliteRepository()

    def runSqlFile(self, pathSqlFile: str) -> None:
        """
        SQLite ejecuta varias sentencias en un solo executescript(): el fichero va entero, sin trocearlo por ";".
        """
        sql = self._getFileContentSql(pathSqlFile)
        if not sql:
            return
        self.executeRaw(sql)

    def runMigrationFileAndSave(self, pathSqlFile: str, migrationFile: str, batch: int) -> None:
        """
        Fichero + registro en migrations en la MISMA transaccion: si el SQL falla no queda aplicado a
        medias ni marcado como ejecutado. Ojo: en SQLite el DDL tambien es transaccional.
        Sin try/except (un repositorio no captura): si algo falla no se llega al commit, la excepcion sube
        hasta el command y SQLite deshace la transaccion abierta al cerrarse la conexion (el command sale con 2).
        """
        self.pdo.execute("BEGIN")
        self._runSqlStatements(self._getFileContentSql(pathSqlFile))
        self._saveMigration(migrationFile, batch)
        self.pdo.execute("COMMIT")

    def _runSqlStatements(self, sql: str) -> None:
        """
        Dentro de la transaccion NO vale executescript(): hace un COMMIT implicito antes de ejecutar y la migracion
        dejaria de ser atomica. Se trocea el fichero con sqlite3.complete_statement (respeta comillas, comentarios y
        triggers) y se lanza cada sentencia con execute().
        """
        if not sql:
            return
        self.logTrait.logSql(sql)
        statement = ""
        for line in sql.splitlines(keepends=True):
            statement += line
            if sqlite3.complete_statement(statement):
                self.pdo.execute(statement)
                statement = ""
        if statement.strip():
            self.pdo.execute(statement)

    def _saveMigration(self, migrationFile: str, batch: int) -> None:
        sql = f"""
        -- saveMigration
        INSERT INTO `migrations` (migration_file, batch)
        VALUES (?, {batch})
        """
        self.execute(sql, [migrationFile])

    def _getFileContentSql(self, pathSqlFile: str) -> str:
        with open(pathSqlFile, "r", encoding="utf-8") as sqlFile:
            return sqlFile.read().strip()
