from Modules.Shared.Application.Primitives import getInt
from Modules.Shared.Infrastructure.Repositories.AbstractSqliteRepository import AbstractSqliteRepository


class RunMigrationsReaderSqliteRepository(AbstractSqliteRepository):

    @staticmethod
    def getInstance() -> "RunMigrationsReaderSqliteRepository":
        return RunMigrationsReaderSqliteRepository()

    def doesMigrationsTableExist(self, tableName: str) -> bool:
        return self.doesTableExist(tableName)

    def getRanMigrationsFiles(self) -> list[dict]:
        sql = """
        -- getRanMigrationsFiles
        SELECT
            id, migration_file
        FROM `migrations`
        WHERE 1=1
        ORDER BY migration_file
        """
        return self.query(sql)

    def getMaxBatch(self) -> int:
        sql = """
        -- getMaxBatch
        SELECT
            MAX(batch) AS max_batch
        FROM `migrations`
        WHERE 1=1
        """
        rows = self.query(sql)
        maxBatch = rows[0].get("max_batch") if rows else None
        return getInt(0 if maxBatch is None else maxBatch)
