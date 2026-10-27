import { fileURLToPath } from "node:url";

// src/Core/Boot -> src/Core -> src -> <project root>. Con "/" tambien en Windows: es como se escriben las rutas en .env
const PATH_ROOT = fileURLToPath(new URL("../../../", import.meta.url)).replaceAll("\\", "/").replace(/\/$/, "");

export const BootPathsEnum = Object.freeze({
    PATH_ROOT: PATH_ROOT,
    PATH_PUBLIC: `${PATH_ROOT}/public`,
    PATH_SRC: `${PATH_ROOT}/src`,
    PATH_DISK_CACHE: `${PATH_ROOT}/storage/cache`,
    PATH_SRC_CONFIG: `${PATH_ROOT}/src/Core/config`,
    PATH_LOGS: `${PATH_ROOT}/storage/logs`,
    PATH_CONSOLE: `${PATH_ROOT}/console`,
    PATH_MIGRATIONS: `${PATH_ROOT}/database/migrations`,
});
