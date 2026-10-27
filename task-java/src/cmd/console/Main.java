package cmd.console;

import java.util.ArrayList;
import java.util.List;

import core.boot.ConsoleMain;
import core.config.Global;

/**
 * Consola de task-java: el console/console.php de task-php. bin/console help
 */
public final class Main {

    private Main() {
    }

    public static void main(String[] args) {
        try {
            Global.appboot_loadenv();
        }
        catch (Exception e) {
            System.err.println(e.getMessage());
            System.exit(2);
        }

        try {
            // el $argv de PHP: el script en la posicion 0 y desde la 1 el alias y sus argumentos
            List<String> argv = new ArrayList<>();
            argv.add("bin/console");
            argv.addAll(List.of(args));
            ConsoleMain.getInstance(argv).exec();
        }
        catch (Exception e) {
            ConsoleMain.debug(e);
            ConsoleMain.onError(e);
        }
    }
}
