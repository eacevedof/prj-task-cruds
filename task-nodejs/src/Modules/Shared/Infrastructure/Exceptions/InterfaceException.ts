/**
 * Lo que tiene toda excepcion controlada (de dominio o FieldsException): su mensaje y un codigo HTTP 4xx. Es el
 * Exception de PHP que reciben respondDomainError() y exitWithDomainError().
 */
export interface InterfaceException {
    readonly message: string;
    getCode(): number;
}

/**
 * El Throwable de PHP: lo que llega a un catch como Error. Lo que se lanza sin ser un Error (un string...) se envuelve.
 */
export function getThrowable(thrown: unknown): Error {
    return thrown instanceof Error ? thrown : new Error(String(thrown));
}

/**
 * getFile() y getLine() de un Throwable: el primer "at ...(fichero:linea:columna)" de la traza.
 */
export function getThrowableFileAndLine(throwable: Error): [string, number] {
    const matches = /^\s+at (?:.*\()?(.+?):(\d+):\d+\)?$/m.exec(throwable.stack ?? "");
    return matches ? [matches[1], Number(matches[2])] : ["", 0];
}

/**
 * getTraceAsString(): las lineas "at ..." de la traza, sin el mensaje.
 */
export function getThrowableTraceAsString(throwable: Error): string {
    return (throwable.stack ?? "")
        .split("\n")
        .filter((line) => line.trimStart().startsWith("at "))
        .map((line) => line.trim())
        .join("\n");
}
