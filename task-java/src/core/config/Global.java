package core.config;

import java.io.IOException;
import java.io.UncheckedIOException;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import core.boot.BootPathsEnum;

import modules.shared.domain.enums.AppEnvKeyEnum;
import modules.shared.infrastructure.components.json.JsonSerializer;
import modules.shared.infrastructure.components.logger.Logger;

/**
 * Las funciones globales de task-php (src/Core/config/global.php): carga del .env, lgerr() y __().
 * El entorno de la app vive aqui: Java no deja escribir variables de entorno, asi que getenv()/putenv() son las de PHP.
 */
public final class Global {

    private static final Pattern QUOTED_VALUE_PATTERN = Pattern.compile("^([\"'])(.*)\\1");
    private static final Pattern INLINE_COMMENT_PATTERN = Pattern.compile("(^|\\s+)#.*$");

    // el .env cargado + lo que fija putenv() (los tests). Las vars REALES del contenedor/OS ganan sobre el fichero
    private static final Map<String, String> ENV_VARS = new ConcurrentHashMap<>();

    private Global() {
    }

    /**
     * Carga <root>/.env y FALLA RUIDOSO si tras cargarlo falta alguna clave obligatoria. Sin .env la app
     * arrancaria degradada en silencio. Las vars REALES del contenedor/OS cuentan: sin fichero pero con las
     * claves en el entorno, arranca.
     * @throws IllegalStateException
     */
    public static void appboot_loadenv() {
        String pathEnv = BootPathsEnum.PATH_ROOT + "/.env";
        appboot_loadenvfile(pathEnv);
        appboot_assertrequiredenvkeys(pathEnv);
    }

    /**
     * getenv(): lo fijado con putenv() o cargado del .env; si no, la variable REAL del OS. null si no existe.
     */
    public static String getenv(String key) {
        String value = ENV_VARS.get(key);
        return value != null ? value : System.getenv(key);
    }

    /**
     * putenv(): gana sobre el .env y sobre la variable real (los tests fijan asi su BD).
     */
    public static void putenv(String key, String value) {
        ENV_VARS.put(key, value);
    }

    /**
     * storage/logs/YYYY-MM-DD-<type>.log (mismo esquema que LogTrait/Logger).
     */
    public static void lgerr(Object mixed, String title, String type) {
        String content = mixed instanceof String string ? string : JsonSerializer.getInstance().getPrettyJson(mixed);
        Logger.getInstance().writeLog(
            type + ".log",
            title.isEmpty() ? "(lgerr)\n" + content : "(lgerr) <<  " + title + " >>\n" + content,
            BootPathsEnum.PATH_LOGS
        );
    }

    /**
     * El __() de task-php: sustituye {0}, {1}... por los args. El idioma de alli sale de $_REQUEST["lang"], que la API
     * nunca rellena (y no hay src/Core/locale): siempre responde en ingles, el msgid tal cual. Aqui igual.
     */
    public static String __(String msgId, Object... args) {
        String msgChanged = msgId.trim();
        if (msgChanged.isEmpty()) {
            return "";
        }
        for (int i = 0; i < args.length; i++) {
            msgChanged = msgChanged.replace("{" + i + "}", String.valueOf(args[i]));
        }
        return msgChanged;
    }

    private static void appboot_loadenvfile(String pathEnv) {
        Path path = Path.of(pathEnv);
        if (!Files.exists(path)) return;

        Map<String, String> replacements = new LinkedHashMap<>();
        replacements.put("%PATH_PUBLIC%", BootPathsEnum.PATH_PUBLIC);
        replacements.put("%PATH_ROOT%", BootPathsEnum.PATH_ROOT);
        replacements.put("%PATH_SRC%", BootPathsEnum.PATH_SRC);
        replacements.put("%PATH_SRC_CONFIG%", BootPathsEnum.PATH_SRC_CONFIG);

        List<String> lines;
        try {
            lines = Files.readAllLines(path, StandardCharsets.UTF_8);
        }
        catch (IOException e) {
            throw new UncheckedIOException(e);
        }

        for (String line : lines) {
            if (line.isEmpty() || line.trim().startsWith("#") || !line.contains("=")) {
                continue;
            }

            int separatorIndex = line.indexOf('=');
            String name = line.substring(0, separatorIndex).trim();
            if (name.isEmpty()) {
                continue;
            }

            // la variable de entorno REAL (contenedor/OS) gana sobre el fichero .env, y lo fijado con putenv() tambien
            if (System.getenv(name) != null || ENV_VARS.containsKey(name)) {
                continue;
            }

            String value = appboot_getenvfilevalue(line.substring(separatorIndex + 1));
            for (Map.Entry<String, String> replacement : replacements.entrySet()) {
                value = value.replace(replacement.getKey(), replacement.getValue());
            }
            ENV_VARS.put(name, value);
        }
    }

    /**
     * Valor tal cual lo escribiría dotenv: entre comillas ("x" o 'x') se toma el interior literal; sin comillas
     * se corta el comentario en línea (" # ..." o un valor que empieza por "#").
     */
    private static String appboot_getenvfilevalue(String rawValue) {
        String value = rawValue.trim();
        Matcher matcher = QUOTED_VALUE_PATTERN.matcher(value);
        if (matcher.find()) {
            return matcher.group(2);
        }
        return INLINE_COMMENT_PATTERN.matcher(value).replaceAll("").trim();
    }

    /**
     * @throws IllegalStateException
     */
    private static void appboot_assertrequiredenvkeys(String pathEnv) {
        List<String> missingKeys = appboot_getmissingenvkeys();
        if (missingKeys.isEmpty()) return;

        String reason = Files.exists(Path.of(pathEnv))
            ? "the .env file lacks them"
            : "missing file " + pathEnv + " (copy .env.example to .env)";
        throw new IllegalStateException(
            "appboot_loadenv: required env keys not set (" + String.join(", ", missingKeys) + "): " + reason
        );
    }

    /**
     * Claves obligatorias ausentes o vacías.
     */
    private static List<String> appboot_getmissingenvkeys() {
        List<String> requiredKeys = List.of(
            AppEnvKeyEnum.APP_ENV,
            AppEnvKeyEnum.APP_DOMAIN,
            AppEnvKeyEnum.APP_SQLITE_PATH
        );
        List<String> missingKeys = new ArrayList<>();
        for (String requiredKey : requiredKeys) {
            String value = getenv(requiredKey);
            if (value == null || value.trim().isEmpty()) {
                missingKeys.add(requiredKey);
            }
        }
        return missingKeys;
    }
}
