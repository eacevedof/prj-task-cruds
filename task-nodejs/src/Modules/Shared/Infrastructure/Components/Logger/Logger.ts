import { appendFileSync, chmodSync, existsSync, mkdirSync, realpathSync } from "node:fs";
import { dirname, parse } from "node:path";

import { EnvironmentReaderRawRepository } from "#app/Modules/Shared/Infrastructure/Repositories/Configuration/EnvironmentReaderRawRepository.ts";

export class Logger {

    static getInstance(): Logger {
        return new Logger();
    }

    writeLog(filePath: string, content: string, logsPath: string = ""): void {
        logsPath = logsPath || EnvironmentReaderRawRepository.getInstance().getLogPath();
        if (!logsPath) return;

        if (!existsSync(logsPath)) {
            mkdirSync(logsPath, { recursive: true, mode: 0o777 });
            if (!existsSync(logsPath)) return;
        }

        const logsFolderPath = realpathSync(logsPath);
        const [today, now] = this.#getUtcDateAndDateTime();
        let logFilePath = `${logsFolderPath}/${filePath}`;

        const finalDir = dirname(logFilePath);
        if (!existsSync(finalDir))
            mkdirSync(finalDir, { recursive: true, mode: 0o777 });

        const pathInfo = parse(logFilePath);
        let ext = pathInfo.ext ? pathInfo.ext.substring(1) : "log";
        if (filePath.includes("sql")) ext = "sql";
        const fileName = `${today}-${pathInfo.name}`;

        logFilePath = `${finalDir}/${fileName}.${ext}`;
        content = content.trim();

        if (ext === "sql") content = this.#getNormalizedMarginForSql(content);

        const isNewFile = !existsSync(logFilePath);
        appendFileSync(logFilePath, `\n[${now}]\n${content}`);
        if (isNewFile) {
            chmodSync(logFilePath, 0o666);
        }
    }

    #getNormalizedMarginForSql(content: string): string {
        content = `${content}\n`;
        return content.replace(/^ {8}/gm, "");
    }

    /**
     * date("Y-m-d") y date("Y-m-d H:i:s") con la zona de la app (UTC).
     */
    #getUtcDateAndDateTime(): [string, string] {
        const [date, time] = new Date().toISOString().split("T");
        return [date, `${date} ${time.substring(0, 8)}`];
    }
}
