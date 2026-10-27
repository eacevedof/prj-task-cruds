import type { DatabaseSync, SQLInputValue } from "node:sqlite";

import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import { DbFactory } from "#app/Modules/Shared/Infrastructure/Factories/DbFactory.ts";
import { LogTrait } from "#app/Modules/Shared/Infrastructure/Traits/LogTrait.ts";

export abstract class AbstractSqliteRepository {

    protected readonly pdo: DatabaseSync;
    protected readonly logTrait: LogTrait;

    // el lastInsertId() de PDO: node:sqlite lo da en el resultado de cada sentencia, no en la conexion
    #lastInsertId = 0;

    constructor() {
        this.pdo = DbFactory.getInstance().getSqlitePdoByEnvConfiguration();
        this.logTrait = LogTrait.getInstance();
    }

    /**
     * SELECT con parametros enlazados (?). Devuelve las filas como objetos (columna => valor).
     */
    protected query(sql: string, params: SQLInputValue[] = []): Primitives[] {
        this.logTrait.logSql(this.#getSqlForLog(sql, params));
        return this.pdo.prepare(sql).all(...params) as Primitives[];
    }

    /**
     * INSERT/UPDATE/DELETE con parametros enlazados. Devuelve las filas afectadas.
     */
    protected execute(sql: string, params: SQLInputValue[] = []): number {
        this.logTrait.logSql(this.#getSqlForLog(sql, params));
        const statementResult = this.pdo.prepare(sql).run(...params);
        this.#lastInsertId = Number(statementResult.lastInsertRowid);
        return Number(statementResult.changes);
    }

    /**
     * Varias sentencias de golpe (p.ej. un fichero .sql). Sin parametros: solo SQL de confianza.
     */
    protected executeRaw(sql: string): void {
        this.logTrait.logSql(sql);
        this.pdo.exec(sql);
    }

    /**
     * El id de la ultima fila insertada por execute().
     */
    protected lastInsertId(): number {
        return this.#lastInsertId;
    }

    protected doesTableExist(tableName: string): boolean {
        const sql = `
        -- doesTableExist
        SELECT
            COUNT(*) AS n_rows
        FROM \`sqlite_master\`
        WHERE 1=1
        AND type = 'table'
        AND name = ?
        `;
        const rows = this.query(sql, [tableName]);
        return Boolean(rows[0]?.n_rows ?? 0);
    }

    /**
     * SOLO para el log: la query con cada parametro en su sitio (strings entre comillas, como PDO::quote), para
     * copiarla de sql.sql y lanzarla tal cual. Nunca se ejecuta: la real va siempre con parametros enlazados.
     * Asume que no hay "?" dentro de literales del SQL (no los hay en los repositorios).
     * Los hashes de password (bcrypt $2y$..., argon2 $argon2...) salen como '***': no deben quedar en sql.sql.
     */
    #getSqlForLog(sql: string, params: SQLInputValue[]): string {
        if (!params.length) return sql;

        const paramsForLog = params.map((param: SQLInputValue): string => {
            if (param === null) return "NULL";
            if (typeof param === "number" || typeof param === "bigint") return String(param);
            if (typeof param !== "string") return "'<blob>'";
            if (/^\$(2[aby]|argon2)/.test(param)) return "'***'";
            return `'${param.replaceAll("'", "''")}'`;
        });

        let position = 0;
        return sql.replace(/\?/g, (): string => paramsForLog[position++] ?? "?");
    }
}
