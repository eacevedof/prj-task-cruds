/**
 * Consola de task-nodejs: el console/console.php de task-php. node cmd/console/main.ts help
 */
import { ConsoleMain } from "#app/Core/Boot/ConsoleMain.ts";
import { appboot_loadenv } from "#app/Core/config/global.ts";

import { getThrowable } from "#app/Modules/Shared/Infrastructure/Exceptions/InterfaceException.ts";

try {
    appboot_loadenv();
}
catch (e) {
    console.error(getThrowable(e).message);
    process.exit(2);
}

try {
    // process.argv[0] es node: desde la 1 es el $argv de PHP (el script y luego el alias)
    ConsoleMain.getInstance(process.argv.slice(1)).exec();
}
catch (e) {
    const throwable = getThrowable(e);
    ConsoleMain.debug(throwable);
    ConsoleMain.onError(throwable);
}
