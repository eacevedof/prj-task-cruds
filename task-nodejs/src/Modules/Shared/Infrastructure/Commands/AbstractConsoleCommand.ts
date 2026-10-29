import { writeSync } from "node:fs";

import { __ } from "#app/Core/config/global.ts";

import type { InterfaceConsole } from "#app/Console/Application/InterfaceConsole.ts";

import { ExceptionEnum } from "#app/Modules/Shared/Domain/Enums/ExceptionEnum.ts";
import { SharedMessageEnum } from "#app/Modules/Shared/Domain/Enums/SharedMessageEnum.ts";
import type { Primitives } from "#app/Modules/Shared/Application/Primitives.ts";
import type { InterfaceException } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";
import { CliColorTrait } from "#app/Modules/Shared/Infrastructure/Traits/CliColorTrait.ts";
import { LogTrait } from "#app/Modules/Shared/Infrastructure/Traits/LogTrait.ts";
import { MailTrait } from "#app/Modules/Shared/Infrastructure/Traits/MailTrait.ts";

const EXIT_CODE_DOMAIN_ERROR = 1;
const EXIT_CODE_INFRASTRUCTURE_ERROR = 2;

const STDOUT = 1;
const STDERR = 2;

/**
 * El AbstractHttpController de la consola (vault: CLI Commands as Controllers): lee --clave=valor, imprime el
 * ResultDto en JSON por stdout (sin color: se puede pasar a jq) y los errores en rojo por stderr con su codigo de salida.
 * 0 ok · 1 error de dominio (lo que en HTTP es un 4xx) · 2 error de infraestructura (el 500, se loguea).
 * Escribe con writeSync: el process.exit() de los errores no corta la salida.
 */
export abstract class AbstractConsoleCommand implements InterfaceConsole {

    // los argumentos que van tras el alias del comando
    protected readonly input: string[];
    protected readonly cliColorTrait: CliColorTrait;
    protected readonly logTrait: LogTrait;
    protected readonly mailTrait: MailTrait;

    constructor(input: string[]) {
        this.input = input;
        this.cliColorTrait = CliColorTrait.getInstance();
        this.logTrait = LogTrait.getInstance();
        this.mailTrait = MailTrait.getInstance();
    }

    abstract run(): void;

    /**
     * --user_name="Ana Lopez" → "Ana Lopez". Si no se pasa, "" (el servicio decide si es obligatorio).
     */
    protected getOptionValue(optionName: string): string {
        const optionPrefix = `--${optionName}=`;
        for (const argument of this.input) {
            if (argument.startsWith(optionPrefix)) {
                return argument.substring(optionPrefix.length);
            }
        }
        return "";
    }

    protected printResult(result: Primitives | unknown[]): void {
        writeSync(STDOUT, `${JSON.stringify(result, null, 4)}\n`);
    }

    protected exitWithDomainError(ex: InterfaceException): never {
        this.#printErrors(ex.getCode(), [ex.message]);
        process.exit(EXIT_CODE_DOMAIN_ERROR);
    }

    /**
     * Como el 500 de la API: se loguea con el metodo que fallo y se avisa por email. A diferencia de la API, quien
     * tiene la consola si ve el mensaje real (logDebugCli), como en los commands de app-anubis.
     */
    protected exitWithUnexpectedError(ex: Error, logTitle: string): never {
        this.logTrait.logException(ex, logTitle);
        this.logTrait.logDebugCli(ex.message, logTitle);
        this.mailTrait.sendMailOnThrowable(ex, logTitle);
        this.#printErrors(
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
            [__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED)]
        );
        process.exit(EXIT_CODE_INFRASTRUCTURE_ERROR);
    }

    /**
     * code: el mismo que daria la API (404, 409, 400...), para que consola y HTTP se lean igual
     */
    #printErrors(code: number, errors: string[]): void {
        writeSync(STDERR, `${this.cliColorTrait.getRed(`Error (${code}):`)}\n`);
        for (const error of errors) {
            writeSync(STDERR, `${this.cliColorTrait.getRed(`  - ${error}`)}\n`);
        }
    }
}
