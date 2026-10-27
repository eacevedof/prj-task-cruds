import { writeSync } from "node:fs";

const STDOUT = 1;

/**
 * Colores ANSI para la consola (copia de app-plataformabase): getXxx() devuelve el texto coloreado para
 * componer una linea, echoXxx() la imprime entera por stdout. Sin dieRed: el codigo de salida lo pone
 * AbstractConsoleCommand (1 dominio, 2 infraestructura).
 * La salida es sincrona (writeSync): un process.exit() justo despues no la corta.
 */
export class CliColorTrait {

    static getInstance(): CliColorTrait {
        return new CliColorTrait();
    }

    echoRed(text: string): void {
        this.#echo(this.getRed(text));
    }

    getRed(text: string): string {
        return `\x1b[91m${text}\x1b[0m`;
    }

    echoGreen(text: string): void {
        this.#echo(this.getGreen(text));
    }

    getGreen(text: string): string {
        return `\x1b[92m${text}\x1b[0m`;
    }

    echoYellow(text: string): void {
        this.#echo(this.getYellow(text));
    }

    getYellow(text: string): string {
        return `\x1b[93m${text}\x1b[0m`;
    }

    echoBlue(text: string): void {
        this.#echo(this.getBlue(text));
    }

    getBlue(text: string): string {
        return `\x1b[94m${text}\x1b[0m`;
    }

    echoWhite(text: string): void {
        this.#echo(this.getWhite(text));
    }

    getWhite(text: string): string {
        return `\x1b[97m${text}\x1b[0m`;
    }

    echoOrange(text: string): void {
        this.#echo(this.getOrange(text));
    }

    getOrange(text: string): string {
        return `\x1b[38;5;214m${text}\x1b[0m`;
    }

    #echo(line: string): void {
        writeSync(STDOUT, `${line}\n`);
    }
}
