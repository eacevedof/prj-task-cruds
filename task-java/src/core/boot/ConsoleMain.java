package core.boot;

import java.util.List;

import console.Commands;
import console.Commands.CommandConfig;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.components.json.JsonSerializer;
import modules.shared.infrastructure.components.uuid.UuidGenerator;
import modules.shared.infrastructure.exceptions.InterfaceException;
import modules.shared.infrastructure.repositories.configuration.EnvironmentReaderRawRepository;

import static core.config.Global.lgerr;

/**
 * El console.php de task-php: bin/console <alias> --clave=valor ... -> el command del alias.
 * Sin el modo --class=... de task-php (invocar cualquier clase por reflexion): alli nadie lo usa.
 */
public final class ConsoleMain {

    private final List<String> argv;

    /**
     * argv: el $argv de PHP, con el script en la posicion 0 y el alias en la 1
     */
    private ConsoleMain(List<String> argv) {
        Sapi.setSapiNameCli();
        this.argv = argv;
    }

    public static ConsoleMain getInstance(List<String> argv) {
        return new ConsoleMain(argv);
    }

    public static void onError(Throwable ex) {
        String uuid = UuidGenerator.getInstance().getUniqueIdWithPrefix("");
        lgerr(ex.getMessage(), "console-exception " + uuid, "error");
        lgerr(InterfaceException.getThrowableTraceAsString(ex), "console-exception " + uuid + " TRACE", "error");
        lgerr(
            InterfaceException.getThrowableFile(ex) + " : (line: " + InterfaceException.getThrowableLine(ex) + ")",
            "file-line " + uuid,
            "error"
        );
    }

    public static void debug(Throwable ex) {
        EnvironmentReaderRawRepository environmentReaderRawRepository = EnvironmentReaderRawRepository.getInstance();
        if (environmentReaderRawRepository.isProduction()) return;

        if (!environmentReaderRawRepository.isDebug()) return;

        System.out.println(JsonSerializer.getInstance().getPrettyJson(Primitives.of(
            "Exception", ex.getMessage(),
            "File", InterfaceException.getThrowableFile(ex) + "(" + InterfaceException.getThrowableLine(ex) + ")",
            "response", 500
        )));
        System.out.flush();
    }

    /**
     * @throws IllegalArgumentException si el alias no tiene command
     */
    public void exec() {
        invokeConsoleServiceWithArgs();
    }

    private void invokeConsoleServiceWithArgs() {
        String alias = argv.size() > 1 ? argv.get(1).trim() : "";
        if (alias.isEmpty()) {
            alias = "help";
        }

        CommandConfig commandConfig = Commands.getCommands().get(alias);
        if (commandConfig == null) {
            throw new IllegalArgumentException("no class found for cmd " + alias);
        }

        //a partir de la pos 2 en adelante son parametros de input
        List<String> input = argv.size() > 2 ? argv.subList(2, argv.size()) : List.of();
        commandConfig.service().apply(input).run();
    }
}
