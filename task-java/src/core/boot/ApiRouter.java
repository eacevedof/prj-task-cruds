package core.boot;

import java.net.URLDecoder;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.function.Function;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import modules.shared.infrastructure.components.httpresponder.HttpResponder;

/**
 * Router REST: casa por METODO + patron de url.
 * - url con tags ":nombre" -> se pasan al controller como argumentos con nombre.
 * - getRunData() devuelve la ruta + "_args"; si la url casa pero el metodo no -> {allowed: [...]}
 *   (405); si no casa ninguna url -> null (404).
 */
public final class ApiRouter {

    private static final Pattern URL_TAG_PATTERN = Pattern.compile(":([a-zA-Z_][a-zA-Z0-9_]*)");

    /**
     * Una entrada de core/routes: lo que ejecuta la ruta recibe los tags de la url (":nombre") como argumentos.
     * allowed: metodos HTTP en minusculas (HEAD va implicito en GET).
     */
    public record Route(String url, Function<Map<String, String>, HttpResponder> controller, List<String> allowed) {
    }

    /**
     * Lo que devuelve getRunData(): la ruta + sus "_args"; si la url casa pero el metodo no, solo "allowed" (405).
     */
    public record RunData(Route route, Map<String, String> args, List<String> allowed) {
    }

    private final List<Route> routes;

    private ApiRouter(List<Route> routes) {
        this.routes = routes;
    }

    public static ApiRouter getInstance(List<Route> routes) {
        return new ApiRouter(routes);
    }

    public RunData getRunData(String requestMethod, String requestUri) {
        requestMethod = requestMethod.toLowerCase(Locale.ROOT);
        //RFC 9110: todo recurso que admite GET admite HEAD
        if (requestMethod.equals("head")) {
            requestMethod = "get";
        }
        String path = getPathWithoutQueryString(requestUri);

        List<String> allowedForPath = new ArrayList<>();
        for (Route route : routes) {
            Map<String, String> args = getArgsIfPathMatches(route.url(), path);
            if (args == null) continue;

            List<String> allowed = route.allowed().isEmpty() ? List.of("get") : route.allowed();
            if (allowed.contains(requestMethod)) {
                return new RunData(route, args, List.of());
            }
            allowedForPath.addAll(allowed);
        }

        return allowedForPath.isEmpty()
            ? null
            : new RunData(null, Map.of(), new ArrayList<>(new LinkedHashSet<>(allowedForPath)));
    }

    private String getPathWithoutQueryString(String requestUri) {
        String path = requestUri.split("[?#]", 2)[0].replaceAll("/+$", "");
        return path.isEmpty() ? "/" : path;
    }

    private Map<String, String> getArgsIfPathMatches(String routeUrl, String path) {
        List<String> tagNames = new ArrayList<>();
        StringBuilder pattern = new StringBuilder("^");
        Matcher tagMatcher = URL_TAG_PATTERN.matcher(routeUrl);
        int lastEnd = 0;
        while (tagMatcher.find()) {
            pattern.append(Pattern.quote(routeUrl.substring(lastEnd, tagMatcher.start()))).append("([^/]+)");
            tagNames.add(tagMatcher.group(1));
            lastEnd = tagMatcher.end();
        }
        pattern.append(Pattern.quote(routeUrl.substring(lastEnd))).append("$");

        Matcher pathMatcher = Pattern.compile(pattern.toString()).matcher(path);
        if (!pathMatcher.matches()) {
            return null;
        }

        Map<String, String> args = new LinkedHashMap<>();
        for (int i = 0; i < tagNames.size(); i++) {
            args.put(tagNames.get(i), getUrlDecoded(pathMatcher.group(i + 1)).trim());
        }
        return args;
    }

    /**
     * urldecode(): "+" es un espacio y una secuencia % mal formada se queda tal cual.
     */
    private String getUrlDecoded(String value) {
        try {
            return URLDecoder.decode(value, StandardCharsets.UTF_8);
        }
        catch (IllegalArgumentException e) {
            return value.replace("+", " ");
        }
    }
}
