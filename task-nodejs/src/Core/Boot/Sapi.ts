/**
 * Equivalente a php_sapi_name(): "server" para la API y "cli" para la consola. Lo fija ConsoleMain al arrancar
 * (setSapiNameCli) y es de solo lectura despues.
 */
export const SAPI_SERVER = "server";
export const SAPI_CLI = "cli";

let sapiName = SAPI_SERVER;

export function setSapiNameCli(): void {
    sapiName = SAPI_CLI;
}

export function getSapiName(): string {
    return sapiName;
}
