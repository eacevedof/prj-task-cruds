import { writeSync } from "node:fs";
import { inspect } from "node:util";

import { BootPathsEnum } from "#app/Core/Boot/BootPathsEnum.ts";
import { getSapiName, SAPI_CLI } from "#app/Core/Boot/Sapi.ts";

import { HttpRequester } from "#app/Modules/Shared/Infrastructure/Components/HttpRequester/HttpRequester.ts";
import { Logger } from "#app/Modules/Shared/Infrastructure/Components/Logger/Logger.ts";
import { ServerIdentifier } from "#app/Modules/Shared/Infrastructure/Components/Server/ServerIdentifier.ts";
import {
    getThrowableFileAndLine,
    getThrowableTraceAsString,
} from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

const STDOUT = 1;

/**
 * Logs de la app en storage/logs/YYYY-MM-DD-<nivel>.log (de app-anubis, Shared/Components/Traits/LogTrait).
 * Cada entrada lleva la ip de quien la provoca (cliente HTTP, o "cli-<ip>" desde consola) y su nivel.
 * Sin el envio a Elastic del original: aqui no hay modulo Elastic.
 */
export class LogTrait {

    static getInstance(): LogTrait {
        return new LogTrait();
    }

    logDebug(mixed: unknown, title: string = ""): void {
        this.#writeLevelLog("debug", "DEBUG", mixed, title);
    }

    logInfo(mixed: unknown, title: string = ""): void {
        this.#writeLevelLog("info", "INFO", mixed, title);
    }

    logSql(sql: string, title: string = ""): void {
        this.#writeLevelLog("sql", "SQL", sql, title);
    }

    logWarning(mixed: unknown, title: string = ""): void {
        this.#writeLevelLog("warning", "WARNING", mixed, title);
    }

    logError(mixed: unknown, title: string = ""): void {
        this.#writeLevelLog("error", "ERROR", mixed, title);
    }

    /**
     * Lo que va en todo catch: fichero, linea, codigo, mensaje y traza.
     */
    logException(throwable: Error, title: string = "ERROR"): void {
        const [file, line] = getThrowableFileAndLine(throwable);
        const code = (throwable as { getCode?: () => number }).getCode?.() ?? 0;
        this.#writeLevelLog("error", "ERROR", [
            `ex file:\n\t${file}`,
            `ex line:\n\t${line}`,
            `ex code:\n\t${code}`,
            `ex message:\n\t${throwable.message}`,
            `ex trace:\n\t${getThrowableTraceAsString(throwable)}`,
        ].join("\n"), title);
    }

    /**
     * Solo en consola: al debug.log y ademas a pantalla con color (fecha, titulo, contenido).
     */
    logDebugCli(mixed: unknown, title: string = ""): void {
        if (getSapiName() !== SAPI_CLI) return;

        const now = new Date().toISOString().replace("T", " ").substring(0, 19);
        const print = this.#getPrintable(mixed);
        Logger.getInstance().writeLog("debug", `[${now}][${title}]\n${print}`, BootPathsEnum.PATH_LOGS);
        writeSync(STDOUT, `\x1b[97m[${now}]\x1b[0m\x1b[93m[${title}]\x1b[0m\n\x1b[92m${print}\x1b[0m\n`);
    }

    #writeLevelLog(fileName: string, level: string, mixed: unknown, title: string): void {
        // en task-php es ["host_ip" => ip] y el implode se queda solo con el valor
        const content = [this.#getRemoteClientIP()];
        if (title) content.push(title);
        content.push(this.#getPrintable(mixed));

        Logger.getInstance().writeLog(fileName, `[${level}] ${content.join("\n")}`, BootPathsEnum.PATH_LOGS);
    }

    /**
     * La misma ip que llega a los dtos (HttpRequester.getClientIp).
     */
    #getRemoteClientIP(): string {
        if (getSapiName() === SAPI_CLI)
            return `cli-${ServerIdentifier.getInstance().getServerIp()}`;

        return HttpRequester.getInstance().getClientIp() || "unknown";
    }

    /**
     * print_r($mixed, true): un string tal cual, el resto legible.
     */
    #getPrintable(mixed: unknown): string {
        return typeof mixed === "string" ? mixed : inspect(mixed, { depth: null, breakLength: 120 });
    }
}
