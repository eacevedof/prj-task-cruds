package modules.shared.infrastructure.components.httprequester;

import java.time.ZoneId;
import java.util.HashSet;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Set;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import modules.shared.application.Primitives;
import modules.shared.domain.enums.LanguageCodeEnum;
import modules.shared.infrastructure.components.json.JsonSerializer;
import modules.shared.infrastructure.components.useragent.UserAgentParser;

/**
 * UNICO punto de lectura de la peticion HTTP (la que guarda HttpRequestContext): body, query, cabeceras y la metadata
 * que viaja con todo dto de entrada (AbstractInputDto). Nadie mas lee la peticion.
 */
public final class HttpRequester {

    private static final int USER_AGENT_MAX_LENGTH = 500;
    private static final Pattern BEARER_PATTERN = Pattern.compile("^Bearer\\s+(\\S+)$", Pattern.CASE_INSENSITIVE);

    // timezone_identifiers_list(): las zonas IANA mas UTC
    private static final Set<String> TIME_ZONE_IDENTIFIERS = getTimeZoneIdentifiers();

    private final HttpRequest httpRequest;

    private HttpRequester() {
        this.httpRequest = HttpRequestContext.getInstance().getHttpRequest();
    }

    public static HttpRequester getInstance() {
        return new HttpRequester();
    }

    /**
     * La API habla JSON (tambien en PUT/PATCH/DELETE): se decodifica el body y, si no es un objeto JSON, post queda
     * vacio.
     */
    public void loadJsonBodyIntoPost() {
        if (!httpRequest.getPost().isEmpty()) return;
        if (httpRequest.getBody().isEmpty()) return;

        Object body = JsonSerializer.getInstance().getDecoded(httpRequest.getBody());
        Map<String, Object> post = new LinkedHashMap<>();
        if (body instanceof Map<?, ?> map) {
            map.forEach((key, value) -> post.put(String.valueOf(key), value));
        }
        else if (body instanceof List<?> list) {
            for (int i = 0; i < list.size(); i++) {
                post.put(String.valueOf(i), list.get(i));
            }
        }
        httpRequest.setPost(post);
    }

    public Map<String, Object> getPost() {
        return httpRequest.getPost();
    }

    public Object getPost(String key, Object defaultValue) {
        return Primitives.getValueOrDefault(httpRequest.getPost(), key, defaultValue);
    }

    /**
     * Campo escalar del body como string. Un array/objeto en un campo escalar se trata como vacio
     * (y lo rechaza la validacion de campos obligatorios) en vez de dar "Array".
     */
    public String getPostString(String key) {
        return getScalarAsString(getPost(key, ""));
    }

    public Map<String, Object> getGet() {
        return httpRequest.getGet();
    }

    public Object getGet(String key, Object defaultValue) {
        return Primitives.getValueOrDefault(httpRequest.getGet(), key, defaultValue);
    }

    public String getGetString(String key) {
        return getScalarAsString(getGet(key, ""));
    }

    public String getRequestMethod() {
        String method = httpRequest.getMethod();
        return (method.isEmpty() ? "get" : method).trim().toLowerCase(Locale.ROOT);
    }

    public String getRequestUri() {
        return httpRequest.getRequestUri().isEmpty() ? "/" : httpRequest.getRequestUri();
    }

    public String getHeaderValueByKey(String key) {
        return httpRequest.getHeaders().getOrDefault(key.toLowerCase(Locale.ROOT), "").trim();
    }

    public String getBearerToken() {
        Matcher matcher = BEARER_PATTERN.matcher(getHeaderValueByKey("Authorization"));
        return matcher.find() ? matcher.group(1) : "";
    }

    public Map<String, Object> getMetadata() {
        String userAgent = getUserAgent();
        UserAgentParser userAgentParser = UserAgentParser.getInstance();
        return Primitives.of(
            "userIpAddress", getClientIp(),
            "userBrowser", userAgentParser.getBrowserName(userAgent),
            "userBrowserVersion", userAgentParser.getBrowserVersion(userAgent),
            "userOs", userAgentParser.getOsName(userAgent),
            "userTimeZone", getTimeZone(),
            "userLanguageCode", getLanguageCode()
        );
    }

    public String getUserAgent() {
        String userAgent = getHeaderValueByKey("User-Agent");
        return userAgent.codePoints()
            .limit(USER_AGENT_MAX_LENGTH)
            .collect(StringBuilder::new, StringBuilder::appendCodePoint, StringBuilder::append)
            .toString();
    }

    public String getClientIp() {
        String clientIp = getHeaderValueByKey("Client-Ip");
        if (!clientIp.isEmpty()) {
            return clientIp;
        }
        // "cliente, proxy1, proxy2": el cliente es el primer salto
        String forwardedForIp = getHeaderValueByKey("X-Forwarded-For").split(",")[0].trim();
        if (!forwardedForIp.isEmpty()) {
            return forwardedForIp;
        }
        if (!httpRequest.getRemoteAddr().isEmpty()) {
            return httpRequest.getRemoteAddr();
        }
        return "127.0.0.1";
    }

    /**
     * Cabecera opcional "X-Time-Zone: Europe/Madrid". Solo si es una zona IANA valida.
     */
    public String getTimeZone() {
        String timeZone = getHeaderValueByKey("X-Time-Zone");
        return TIME_ZONE_IDENTIFIERS.contains(timeZone) ? timeZone : "";
    }

    /**
     * "es-ES,es;q=0.9,en;q=0.8" -> "es": la primera preferencia, si la API la entiende.
     */
    public String getLanguageCode() {
        String acceptLanguage = getHeaderValueByKey("Accept-Language");
        String firstLanguage = acceptLanguage.split(",")[0].trim();
        String languageCode = firstLanguage.substring(0, Math.min(2, firstLanguage.length())).toLowerCase(Locale.ROOT);
        return LanguageCodeEnum.VALUES.contains(languageCode) ? languageCode : LanguageCodeEnum.EN;
    }

    /**
     * is_scalar() ? trim((string) $value) : "". false es "" y true es "1", como en PHP.
     */
    private String getScalarAsString(Object value) {
        if (value instanceof String || value instanceof Number || value instanceof Boolean) {
            return Primitives.getTrimmedString(value);
        }
        return "";
    }

    private static Set<String> getTimeZoneIdentifiers() {
        Set<String> timeZoneIdentifiers = new HashSet<>(ZoneId.getAvailableZoneIds());
        timeZoneIdentifiers.add("UTC");
        return timeZoneIdentifiers;
    }
}
