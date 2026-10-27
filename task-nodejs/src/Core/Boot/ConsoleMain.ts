import { writeSync } from "node:fs";

import { setSapiNameCli } from "#app/Core/Boot/Sapi.ts";
import { lgerr } from "#app/Core/config/global.ts";

import { getCommands } from "#console/commands.ts";

import { UuidGenerator } from "#app/Modules/Shared/Infrastructure/Components/Uuid/UuidGenerator.ts";
import {
    getThrowableFileAndLine,
    getThrowableTraceAsString,
} from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { EnvironmentReaderRawRepository } from "#app/Modules/Shared/Infrastructure/Repositories/Configuration/EnvironmentReaderRawRepository.ts";

const STDOUT = 1;

/**
 * El console.php de task-php: node cmd/console/main.ts <alias> --clave=valor ... -> el command del alias.
 * Sin el modo --class=... de task-php (invocar cualquier clase por reflexion): alli nadie lo usa.
 */
export class ConsoleMain {

    readonly #argv: string[];

    /**
     * argv: el $argv de PHP, con el script en la posicion 0 y el alias en la 1
     */
    constructor(argv: string[] = []) {
        setSapiNameCli();
        this.#argv = argv;
    }

    static getInstance(argv: string[] = []): ConsoleMain {
        return new ConsoleMain(argv);
    }

    static onError(ex: Error): void {
        const uuid = UuidGenerator.getInstance().getUniqueIdWithPrefix("");
        const [file, line] = getThrowableFileAndLine(ex);
        lgerr(ex.message, `console-exception ${uuid}`, "error");
        lgerr(getThrowableTraceAsString(ex), `console-exception ${uuid} TRACE`, "error");
        lgerr(`${file} : (line: ${line})`, `file-line ${uuid}`, "error");
    }

    static debug(ex: Error): void {
        const environmentReaderRawRepository = EnvironmentReaderRawRepository.getInstance();
        if (environmentReaderRawRepository.isProduction()) return;

        if (!environmentReaderRawRepository.isDebug()) return;

        const [file, line] = getThrowableFileAndLine(ex);
        writeSync(STDOUT, `${JSON.stringify({
            Exception: ex.message,
            File: `${file}(${line})`,
            response: 500,
        }, null, 4)}\n`);
    }

    /**
     * @throws Error si el alias no tiene command
     */
    exec(): void {
        this.#invokeConsoleServiceWithArgs();
    }

    #invokeConsoleServiceWithArgs(): void {
        let alias = (this.#argv[1] ?? "").trim();
        if (!alias) {
            alias = "help";
        }

        const commandConfig = getCommands()[alias];
        if (!commandConfig) {
            throw new Error(`no class found for cmd ${alias}`);
        }

        //a partir de la pos 2 en adelante son parametros de input
        const input = this.#argv.slice(2);
        commandConfig.service(input).run();
    }
}
