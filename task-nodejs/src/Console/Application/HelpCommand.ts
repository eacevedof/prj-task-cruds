import { writeSync } from "node:fs";

import { getCommands } from "#console/commands.ts";

import { AbstractConsoleCommand } from "#app/Modules/Shared/Infrastructure/Commands/AbstractConsoleCommand.ts";

const STDOUT = 1;

/**
 * node cmd/console/main.ts help   (o sin alias)
 * Lista console/commands.ts: por comando, que hace y la llamada tal cual se escribe (la clase no: es detalle interno).
 * En verde el comando, en amarillo la llamada que se copia.
 */
export class HelpCommand extends AbstractConsoleCommand {

    run(): void {
        const lines: string[] = [];
        let i = 1;
        for (const [command, conf] of Object.entries(getCommands())) {
            const info = conf.info ? `\n\t${conf.info}` : "";
            const call = `node cmd/console/main.ts ${command} ${conf.example}`.trim();
            lines.push(`\n(${i}) ${this.cliColorTrait.getGreen(command)}:${info}\n\t${this.cliColorTrait.getYellow(call)}`);
            i++;
        }
        this.cliColorTrait.echoBlue("help menu");
        writeSync(STDOUT, `${lines.join("")}\n`);
    }
}
