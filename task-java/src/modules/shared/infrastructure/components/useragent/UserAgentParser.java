package modules.shared.infrastructure.components.useragent;

import java.util.LinkedHashMap;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

/**
 * Navegador, version y sistema operativo a partir del User-Agent. Deteccion por patrones (sin vendor):
 * suficiente para metadata/trazas, no para decidir permisos (el User-Agent lo escribe el cliente).
 */
public final class UserAgentParser {

    /**
     * El ORDEN importa: Edge y Opera tambien dicen "Chrome", Chrome tambien dice "Safari".
     * nombre => patron cuyo grupo 1 es la version.
     */
    private static final Map<String, Pattern> BROWSER_PATTERNS = new LinkedHashMap<>();

    /**
     * El ORDEN importa: Android dice "Linux", iPhone/iPad dicen "Mac OS X".
     */
    private static final Map<String, Pattern> OS_PATTERNS = new LinkedHashMap<>();

    static {
        BROWSER_PATTERNS.put("Edge", Pattern.compile("Edg(?:e|A|iOS)?/([\\d.]+)"));
        BROWSER_PATTERNS.put("Opera", Pattern.compile("(?:OPR|Opera)/([\\d.]+)"));
        BROWSER_PATTERNS.put("Firefox", Pattern.compile("(?:Firefox|FxiOS)/([\\d.]+)"));
        BROWSER_PATTERNS.put("Chrome", Pattern.compile("(?:Chrome|CriOS)/([\\d.]+)"));
        BROWSER_PATTERNS.put("Safari", Pattern.compile("Version/([\\d.]+).*Safari/"));
        BROWSER_PATTERNS.put("Postman", Pattern.compile("PostmanRuntime/([\\d.]+)"));
        BROWSER_PATTERNS.put("curl", Pattern.compile("^curl/([\\d.]+)"));

        OS_PATTERNS.put("Android", Pattern.compile("Android"));
        OS_PATTERNS.put("iOS", Pattern.compile("iPhone|iPad|iPod"));
        OS_PATTERNS.put("Windows", Pattern.compile("Windows"));
        OS_PATTERNS.put("Mac", Pattern.compile("Macintosh|Mac OS X"));
        OS_PATTERNS.put("Ubuntu", Pattern.compile("Ubuntu"));
        OS_PATTERNS.put("Linux", Pattern.compile("Linux"));
    }

    public static UserAgentParser getInstance() {
        return new UserAgentParser();
    }

    public String getBrowserName(String userAgent) {
        for (Map.Entry<String, Pattern> browserPattern : BROWSER_PATTERNS.entrySet()) {
            if (browserPattern.getValue().matcher(userAgent).find()) return browserPattern.getKey();
        }
        return "";
    }

    public String getBrowserVersion(String userAgent) {
        for (Pattern pattern : BROWSER_PATTERNS.values()) {
            Matcher matcher = pattern.matcher(userAgent);
            if (matcher.find()) return matcher.group(1);
        }
        return "";
    }

    public String getOsName(String userAgent) {
        for (Map.Entry<String, Pattern> osPattern : OS_PATTERNS.entrySet()) {
            if (osPattern.getValue().matcher(userAgent).find()) return osPattern.getKey();
        }
        return "";
    }
}
