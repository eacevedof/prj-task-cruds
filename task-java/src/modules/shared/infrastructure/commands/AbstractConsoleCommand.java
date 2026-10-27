package modules.shared.infrastructure.commands;

import java.util.List;

import console.application.InterfaceConsole;

import modules.shared.domain.enums.ExceptionEnum;
import modules.shared.domain.enums.SharedMessageEnum;
import modules.shared.infrastructure.components.json.JsonSerializer;
import modules.shared.infrastructure.exceptions.InterfaceException;
import modules.shared.infrastructure.traits.CliColorTrait;
import modules.shared.infrastructure.traits.LogTrait;
import modules.shared.infrastructure.traits.MailTrait;

import static core.config.Global.__;

/**
 * El AbstractApiController de la consola (vault: CLI Commands as Controllers): lee --clave=valor, imprime el
 * ResultDto en JSON por stdout (sin color: se puede pasar a jq) y los errores en rojo por stderr con su codigo de salida.
 * 0 ok · 1 error de dominio (lo que en HTTP es un 4xx) · 2 error de infraestructura (el 500, se loguea).
 */
public abstract class AbstractConsoleCommand implements InterfaceConsole {

    private static final int EXIT_CODE_DOMAIN_ERROR = 1;
    private static final int EXIT_CODE_INFRASTRUCTURE_ERROR = 2;

    // los argumentos que van tras el alias del comando
    protected final List<String> input;
    protected final CliColorTrait cliColorTrait;
    protected final LogTrait logTrait;
    protected final MailTrait mailTrait;

    protected AbstractConsoleCommand(List<String> input) {
        this.input = input;
        this.cliColorTrait = CliColorTrait.getInstance();
        this.logTrait = LogTrait.getInstance();
        this.mailTrait = MailTrait.getInstance();
    }

    @Override
    public abstract void run();

    /**
     * --user_name="Ana Lopez" → "Ana Lopez". Si no se pasa, "" (el servicio decide si es obligatorio).
     */
    protected String getOptionValue(String optionName) {
        String optionPrefix = "--" + optionName + "=";
        for (String argument : input) {
            if (argument.startsWith(optionPrefix)) {
                return argument.substring(optionPrefix.length());
            }
        }
        return "";
    }

    protected void printResult(Object result) {
        System.out.println(JsonSerializer.getInstance().getPrettyJson(result));
        System.out.flush();
    }

    protected void exitWithDomainError(InterfaceException ex) {
        printErrors(ex.getCode(), List.of(ex.getMessage()));
        System.exit(EXIT_CODE_DOMAIN_ERROR);
    }

    /**
     * Como el 500 de la API: se loguea con el metodo que fallo y se avisa por email. A diferencia de la API, quien
     * tiene la consola si ve el mensaje real (logDebugCli), como en los commands de app-anubis.
     */
    protected void exitWithUnexpectedError(Throwable ex, String logTitle) {
        logTrait.logException(ex, logTitle);
        logTrait.logDebugCli(ex.getMessage(), logTitle);
        mailTrait.sendMailOnThrowable(ex, logTitle);
        printErrors(
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
            List.of(__(SharedMessageEnum.AN_UNEXPECTED_ERROR_HAS_OCCURRED))
        );
        System.exit(EXIT_CODE_INFRASTRUCTURE_ERROR);
    }

    /**
     * code: el mismo que daria la API (404, 409, 400...), para que consola y HTTP se lean igual
     */
    private void printErrors(int code, List<String> errors) {
        System.err.println(cliColorTrait.getRed("Error (" + code + "):"));
        for (String error : errors) {
            System.err.println(cliColorTrait.getRed("  - " + error));
        }
        System.err.flush();
    }
}
