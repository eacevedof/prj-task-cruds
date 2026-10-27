import { getInt } from "#app/Modules/Shared/Application/Primitives.ts";
import { AbstractSqliteRepository } from "#app/Modules/Shared/Infrastructure/Repositories/AbstractSqliteRepository.ts";

export class RunMigrationsReaderSqliteRepository extends AbstractSqliteRepository {

    static getInstance(): RunMigrationsReaderSqliteRepository {
        return new RunMigrationsReaderSqliteRepository();
    }

    doesMigrationsTableExist(tableName: string): boolean {
        return this.doesTableExist(tableName);
    }

    getRanMigrationsFiles(): { id: number; migration_file: string }[] {
        const sql = `
        -- getRanMigrationsFiles
        SELECT
            id, migration_file
        FROM \`migrations\`
        WHERE 1=1
        ORDER BY migration_file
        `;
        return this.query(sql) as { id: number; migration_file: string }[];
    }

    getMaxBatch(): number {
        const sql = `
        -- getMaxBatch
        SELECT
            MAX(batch) AS max_batch
        FROM \`migrations\`
        WHERE 1=1
        `;
        const rows = this.query(sql);
        return getInt(rows[0]?.max_batch ?? 0);
    }
}
