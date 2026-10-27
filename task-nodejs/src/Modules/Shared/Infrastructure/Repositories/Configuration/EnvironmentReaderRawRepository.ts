import { ENV } from "#app/Core/Boot/ENV.ts";

import { AppEnvKeyEnum } from "#app/Modules/Shared/Domain/Enums/AppEnvKeyEnum.ts";

// version publicada en /api/v1: es la del CONTRATO (la misma que task-php). Al subirla, anotar el cambio en
// changelog.md
const APP_VERSION = "2.0.0";
const APP_VERSION_UPDATE = "2026-10-25";

let instance: EnvironmentReaderRawRepository | null = null;

/**
 * Fuente ÚNICA de lectura de entorno: un getter semántico por cada clave del .env que la app consume
 * (claves en AppEnvKeyEnum; las que nadie lee no se mapean). Patrón getInstance singleton. El .env lo carga
 * appboot_loadenv() (Core/config/global.ts) en process.env antes del primer getInstance(); las vars REALES del
 * contenedor/OS ganan sobre el fichero.
 */
export class EnvironmentReaderRawRepository {

    static getInstance(): EnvironmentReaderRawRepository {
        if (instance) return instance;

        instance = new EnvironmentReaderRawRepository();
        return instance;
    }

    getAppVersion(): string {
        return APP_VERSION;
    }

    getAppVersionUpdate(): string {
        return APP_VERSION_UPDATE;
    }

    getBaseUrl(): string {
        return this.#get(AppEnvKeyEnum.APP_BASE_URL).replace(/\/+$/, "");
    }

    getEmailTo(): string {
        return this.#get(AppEnvKeyEnum.APP_EMAIL_TO);
    }

    getEmailFrom(): string {
        return this.#get(AppEnvKeyEnum.APP_EMAIL_FROM);
    }

    /**
     * El sendmail_path del php.ini de task-php: el comando al que FuncMailer entrega el email.
     */
    getSendmailPath(): string {
        return this.#get(AppEnvKeyEnum.APP_SENDMAIL_PATH);
    }

    getLogPath(): string {
        return this.#get(AppEnvKeyEnum.APP_LOG_PATH);
    }

    getAppName(): string {
        return this.#get(AppEnvKeyEnum.APP_NAME);
    }

    getEnvironment(): string {
        return this.#get(AppEnvKeyEnum.APP_ENV);
    }

    isProduction(): boolean {
        return this.getEnvironment() === ENV.PRODUCTION;
    }

    /**
     * filter_var(FILTER_VALIDATE_BOOLEAN): "1", "true", "on", "yes".
     */
    isDebug(): boolean {
        return ["1", "true", "on", "yes"].includes(this.#get(AppEnvKeyEnum.APP_DEBUG).toLowerCase());
    }

    isLocal(): boolean {
        return this.getEnvironment() === ENV.LOCAL;
    }

    getSqlitePath(): string {
        return this.#get(AppEnvKeyEnum.APP_SQLITE_PATH);
    }

    isDev(): boolean {
        return this.getEnvironment() === ENV.DEVELOP;
    }

    isTest(): boolean {
        return this.getEnvironment() === ENV.TEST;
    }

    getColor(): string {
        return ENV.COLORS[this.getEnvironment()] ?? "";
    }

    getDefaultLang(): string {
        return this.#get(AppEnvKeyEnum.APP_DEFAULT_LANG);
    }

    getDomain(): string {
        return this.#get(AppEnvKeyEnum.APP_DOMAIN);
    }

    /**
     * Se lee en cada llamada: los tests fijan sus variables antes del primer uso y nada se queda con un valor viejo.
     */
    #get(key: string): string {
        return (process.env[key] ?? "").trim();
    }
}
