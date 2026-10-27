import { existsSync, readFileSync } from "node:fs";
import { inspect } from "node:util";

import { BootPathsEnum } from "#app/Core/Boot/BootPathsEnum.ts";

import { AppEnvKeyEnum } from "#app/Modules/Shared/Domain/Enums/AppEnvKeyEnum.ts";
import { Logger } from "#app/Modules/Shared/Infrastructure/Components/Logger/Logger.ts";

/**
 * Carga <root>/.env y FALLA RUIDOSO si tras cargarlo falta alguna clave obligatoria. Sin .env la app
 * arrancaria degradada en silencio. Las vars REALES del contenedor/OS cuentan: sin fichero pero con las
 * claves en el entorno, arranca.
 * @throws Error
 */
export function appboot_loadenv(): void {
    const pathEnv = `${BootPathsEnum.PATH_ROOT}/.env`;
    appboot_loadenvfile(pathEnv);
    appboot_assertrequiredenvkeys(pathEnv);
}

function appboot_loadenvfile(pathEnv: string): void {
    if (!existsSync(pathEnv)) return;

    const replacements: Record<string, string> = {
        "%PATH_PUBLIC%": BootPathsEnum.PATH_PUBLIC,
        "%PATH_ROOT%": BootPathsEnum.PATH_ROOT,
        "%PATH_SRC%": BootPathsEnum.PATH_SRC,
        "%PATH_SRC_CONFIG%": BootPathsEnum.PATH_SRC_CONFIG,
    };

    const lines = readFileSync(pathEnv, "utf8").split(/\r?\n/).filter((line) => line !== "");
    for (const line of lines) {
        if (line.trim().startsWith("#") || !line.includes("=")) {
            continue;
        }

        const separatorIndex = line.indexOf("=");
        const name = line.substring(0, separatorIndex).trim();
        if (name === "") {
            continue;
        }

        // la variable de entorno REAL (contenedor/OS) gana sobre el fichero .env
        if (name in process.env) {
            continue;
        }

        let value = appboot_getenvfilevalue(line.substring(separatorIndex + 1));
        for (const [placeholder, path] of Object.entries(replacements)) {
            value = value.replaceAll(placeholder, path);
        }
        process.env[name] = value;
    }
}

/**
 * Valor tal cual lo escribiría dotenv: entre comillas ("x" o 'x') se toma el interior literal; sin comillas
 * se corta el comentario en línea (" # ..." o un valor que empieza por "#").
 */
function appboot_getenvfilevalue(rawValue: string): string {
    const value = rawValue.trim();
    const matches = /^(["'])(.*)\1/.exec(value);
    if (matches) {
        return matches[2];
    }
    return value.replace(/(^|\s+)#.*$/, "").trim();
}

/**
 * @throws Error
 */
function appboot_assertrequiredenvkeys(pathEnv: string): void {
    const missingKeys = appboot_getmissingenvkeys();
    if (!missingKeys.length) return;

    const reason = existsSync(pathEnv)
        ? "the .env file lacks them"
        : `missing file ${pathEnv} (copy .env.example to .env)`;
    throw new Error(`appboot_loadenv: required env keys not set (${missingKeys.join(", ")}): ${reason}`);
}

/**
 * Claves obligatorias ausentes o vacías.
 */
function appboot_getmissingenvkeys(): string[] {
    const requiredKeys = [
        AppEnvKeyEnum.APP_ENV,
        AppEnvKeyEnum.APP_DOMAIN,
        AppEnvKeyEnum.APP_SQLITE_PATH,
    ];
    return requiredKeys.filter((key) => (process.env[key] ?? "").trim() === "");
}

/**
 * storage/logs/YYYY-MM-DD-<type>.log (mismo esquema que LogTrait/Logger).
 */
export function lgerr(mixed: unknown, title: string = "", type: string = "error"): void {
    const content = typeof mixed === "string" ? mixed : inspect(mixed, { depth: null, breakLength: 120 });
    Logger.getInstance().writeLog(
        `${type}.log`,
        title ? `(lgerr) <<  ${title} >>\n${content}` : `(lgerr)\n${content}`,
        BootPathsEnum.PATH_LOGS
    );
}

/**
 * El __() de task-php: sustituye {0}, {1}... por los args. El idioma de alli sale de $_REQUEST["lang"], que la API
 * nunca rellena (y no hay src/Core/locale): siempre responde en ingles, el msgid tal cual. Aqui igual.
 */
export function __(msgId: string, ...args: (string | number)[]): string {
    let msgChanged = msgId.trim();
    if (!msgChanged) {
        return "";
    }
    args.forEach((arg, index) => {
        msgChanged = msgChanged.replaceAll(`{${index}}`, String(arg));
    });
    return msgChanged;
}
