package modules.shared.infrastructure.traits;

import java.time.LocalDateTime;
import java.time.ZoneOffset;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;

import core.boot.BootPathsEnum;
import core.boot.Sapi;

import modules.shared.infrastructure.components.httprequester.HttpRequester;
import modules.shared.infrastructure.components.json.JsonSerializer;
import modules.shared.infrastructure.components.logger.Logger;
import modules.shared.infrastructure.components.server.ServerIdentifier;
import modules.shared.infrastructure.exceptions.InterfaceException;

/**
 * Logs de la app en storage/logs/YYYY-MM-DD-<nivel>.log (de app-anubis, Shared/Components/Traits/LogTrait).
 * Cada entrada lleva la ip de quien la provoca (cliente HTTP, o "cli-<ip>" desde consola) y su nivel.
 * Sin el envio a Elastic del original: aqui no hay modulo Elastic.
 */
public final class LogTrait {

    private static final DateTimeFormatter FORMAT_DATE_TIME = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss");

    public static LogTrait getInstance() {
        return new LogTrait();
    }

    public void logDebug(Object mixed) {
        logDebug(mixed, "");
    }

    public void logDebug(Object mixed, String title) {
        writeLevelLog("debug", "DEBUG", mixed, title);
    }

    public void logInfo(Object mixed, String title) {
        writeLevelLog("info", "INFO", mixed, title);
    }

    public void logSql(String sql) {
        writeLevelLog("sql", "SQL", sql, "");
    }

    public void logWarning(Object mixed, String title) {
        writeLevelLog("warning", "WARNING", mixed, title);
    }

    public void logError(Object mixed, String title) {
        writeLevelLog("error", "ERROR", mixed, title);
    }

    /**
     * Lo que va en todo catch: fichero, linea, codigo, mensaje y traza.
     */
    public void logException(Throwable throwable, String title) {
        int code = throwable instanceof InterfaceException interfaceException ? interfaceException.getCode() : 0;
        writeLevelLog("error", "ERROR", String.join("\n",
            "ex file:\n\t" + InterfaceException.getThrowableFile(throwable),
            "ex line:\n\t" + InterfaceException.getThrowableLine(throwable),
            "ex code:\n\t" + code,
            "ex message:\n\t" + throwable.getMessage(),
            "ex trace:\n\t" + InterfaceException.getThrowableTraceAsString(throwable)
        ), title);
    }

    /**
     * Solo en consola: al debug.log y ademas a pantalla con color (fecha, titulo, contenido).
     */
    public void logDebugCli(Object mixed, String title) {
        if (!Sapi.getSapiName().equals(Sapi.SAPI_CLI)) return;

        String now = LocalDateTime.now(ZoneOffset.UTC).format(FORMAT_DATE_TIME);
        String print = getPrintable(mixed);
        Logger.getInstance().writeLog("debug", "[" + now + "][" + title + "]\n" + print, BootPathsEnum.PATH_LOGS);
        System.out.println("\u001b[97m[" + now + "]\u001b[0m\u001b[93m[" + title + "]\u001b[0m\n\u001b[92m" + print + "\u001b[0m");
        System.out.flush();
    }

    private void writeLevelLog(String fileName, String level, Object mixed, String title) {
        // en task-php es ["host_ip" => ip] y el implode se queda solo con el valor
        List<String> content = new ArrayList<>();
        content.add(getRemoteClientIP());
        if (!title.isEmpty()) content.add(title);
        content.add(getPrintable(mixed));

        Logger.getInstance().writeLog(fileName, "[" + level + "] " + String.join("\n", content), BootPathsEnum.PATH_LOGS);
    }

    /**
     * La misma ip que llega a los dtos (HttpRequester.getClientIp).
     */
    private String getRemoteClientIP() {
        if (Sapi.getSapiName().equals(Sapi.SAPI_CLI))
            return "cli-" + ServerIdentifier.getInstance().getServerIp();

        String clientIp = HttpRequester.getInstance().getClientIp();
        return clientIp.isEmpty() ? "unknown" : clientIp;
    }

    /**
     * print_r($mixed, true): un string tal cual, un array legible (JSON con sangria).
     */
    private String getPrintable(Object mixed) {
        if (mixed instanceof String string) return string;
        if (mixed instanceof Map<?, ?> || mixed instanceof List<?>) return JsonSerializer.getInstance().getPrettyJson(mixed);
        return String.valueOf(mixed);
    }
}
