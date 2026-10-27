import { readdirSync } from "node:fs";
import { extname } from "node:path";

import { BootPathsEnum } from "#app/Core/Boot/BootPathsEnum.ts";

import type { RunMigrationsDto } from "#app/Modules/Devops/Application/RunMigrations/RunMigrationsDto.ts";
import { RunMigrationsResultDto } from "#app/Modules/Devops/Application/RunMigrations/RunMigrationsResultDto.ts";
import { RunMigrationsReaderSqliteRepository } from "#app/Modules/Devops/Infrastructure/Repositories/RunMigrationsReaderSqliteRepository.ts";
import { RunMigrationsWriterSqliteRepository } from "#app/Modules/Devops/Infrastructure/Repositories/RunMigrationsWriterSqliteRepository.ts";

const MIGRATIONS_TABLE = "migrations";
const CREATE_MIGRATIONS_TABLE_FILE = "0000-create-migrations-table.sql";

/**
 * Ejecuta, en orden de nombre, los .sql de database/migrations que no esten en la tabla migrations.
 * Todos los pendientes de una ejecucion comparten batch (max(batch) + 1).
 * node cmd/console/main.ts run-migrations
 */
export class RunMigrationsService {

    readonly #runMigrationsReaderSqliteRepository: RunMigrationsReaderSqliteRepository;
    readonly #runMigrationsWriterSqliteRepository: RunMigrationsWriterSqliteRepository;

    #sqlMigrationsFiles: string[] = [];
    #sqlFilesToBeRan: string[] = [];
    #messages: string[] = [];

    constructor() {
        this.#runMigrationsReaderSqliteRepository = RunMigrationsReaderSqliteRepository.getInstance();
        this.#runMigrationsWriterSqliteRepository = RunMigrationsWriterSqliteRepository.getInstance();
    }

    static getInstance(): RunMigrationsService {
        return new RunMigrationsService();
    }

    invoke(_runMigrationsDto: RunMigrationsDto): RunMigrationsResultDto {
        this.#createMigrationsTableIfDoesNotExist();

        this.#loadMigrationsFiles();
        if (!this.#sqlMigrationsFiles.length) {
            this.#addMessage("No migrations files found");
            return RunMigrationsResultDto.fromPrimitives({ messages: this.#messages });
        }

        this.#loadFilesToBeRan();
        if (!this.#sqlFilesToBeRan.length) {
            this.#addMessage("No migrations files to be ran");
            return RunMigrationsResultDto.fromPrimitives({ messages: this.#messages });
        }

        this.#runMigrations();
        return RunMigrationsResultDto.fromPrimitives({ messages: this.#messages });
    }

    #createMigrationsTableIfDoesNotExist(): void {
        if (this.#runMigrationsReaderSqliteRepository.doesMigrationsTableExist(MIGRATIONS_TABLE)) {
            this.#addMessage("migrations table wont be created, already exists");
            return;
        }
        this.#addMessage("creating migrations table");
        this.#runMigrationsWriterSqliteRepository.runSqlFile(
            this.#getPathSqlFile(CREATE_MIGRATIONS_TABLE_FILE)
        );
    }

    #loadMigrationsFiles(): void {
        const sqlFiles = readdirSync(BootPathsEnum.PATH_MIGRATIONS).filter(
            (file) => extname(file) === ".sql" && file !== CREATE_MIGRATIONS_TABLE_FILE
        );
        if (!sqlFiles.length)
            return;

        this.#sqlMigrationsFiles = sqlFiles.sort();
    }

    #loadFilesToBeRan(): void {
        const ranFilesNames = this.#runMigrationsReaderSqliteRepository.getRanMigrationsFiles()
            .map((ranMigrationFile) => ranMigrationFile.migration_file);
        for (const sqlFile of this.#sqlMigrationsFiles) {
            if (ranFilesNames.includes(sqlFile))
                continue;
            this.#sqlFilesToBeRan.push(sqlFile);
        }
    }

    #runMigrations(): void {
        const batch = this.#runMigrationsReaderSqliteRepository.getMaxBatch() + 1;
        this.#addMessage(`running migrations batch: ${batch}`);
        for (const sqlFile of this.#sqlFilesToBeRan) {
            this.#addMessage(`running migration: ${sqlFile}`);
            this.#runMigrationsWriterSqliteRepository.runMigrationFileAndSave(
                this.#getPathSqlFile(sqlFile),
                sqlFile,
                batch
            );
            this.#addMessage(`saved migration: ${sqlFile}`);
        }
    }

    #getPathSqlFile(sqlFile: string): string {
        return `${BootPathsEnum.PATH_MIGRATIONS}/${sqlFile}`;
    }

    /**
     * Traza para el ResultDto: el command la imprime y la deja en el log de debug.
     */
    #addMessage(message: string): void {
        this.#messages.push(message);
    }
}
