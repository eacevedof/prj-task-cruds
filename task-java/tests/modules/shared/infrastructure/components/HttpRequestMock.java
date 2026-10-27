package modules.shared.infrastructure.components;

import java.util.LinkedHashMap;
import java.util.Locale;
import java.util.Map;

import modules.shared.infrastructure.components.httprequester.HttpRequest;
import modules.shared.infrastructure.components.httprequester.HttpRequestContext;
import modules.shared.infrastructure.components.json.JsonSerializer;

/**
 * Peticion simulada: cabeceras e ip (el Bearer que lee getAuthUserOrFail, User-Agent / Accept-Language / X-Time-Zone
 * que lee HttpRequester), el body JSON ya en post (lo que hace IndexMain), la query en get y los tags de la url
 * (":userUuid") como argumentos del controller. getMockedInstance() la carga en HttpRequestContext (como PHP rellena
 * sus superglobales) y devuelve esos argumentos.
 */
public final class HttpRequestMock {

    private String bearerToken = "";
    private String ipAddress = "";
    private Map<String, String> headers = new LinkedHashMap<>();
    private Map<String, String> routeParams = new LinkedHashMap<>();
    private Map<String, Object> query = new LinkedHashMap<>();
    private Map<String, Object> postRaw = new LinkedHashMap<>();

    public static HttpRequestMock getInstance() {
        return new HttpRequestMock();
    }

    public HttpRequestMock setBearerToken(String bearerToken) {
        this.bearerToken = bearerToken;
        return this;
    }

    public HttpRequestMock setIpAddress(String ipAddress) {
        this.ipAddress = ipAddress;
        return this;
    }

    /**
     * Cabeceras con su nombre HTTP: {"User-Agent": "...", "Accept-Language": "es-ES"}.
     */
    public HttpRequestMock setHeaders(Map<String, String> headers) {
        this.headers = headers;
        return this;
    }

    public HttpRequestMock setRouteParams(Map<String, String> routeParams) {
        this.routeParams = routeParams;
        return this;
    }

    public HttpRequestMock setGetParams(Map<String, Object> queryParams) {
        this.query = queryParams;
        return this;
    }

    public HttpRequestMock setPostRawParams(Map<String, Object> postParams) {
        this.postRaw = postParams;
        return this;
    }

    /**
     * @return los argumentos con nombre del controller
     */
    public Map<String, String> getMockedInstance() {
        Map<String, String> requestHeaders = new LinkedHashMap<>();
        requestHeaders.put("content-type", "application/json");
        if (!bearerToken.isEmpty()) {
            requestHeaders.put("authorization", "Bearer " + bearerToken);
        }
        headers.forEach((headerName, headerValue) -> requestHeaders.put(headerName.toLowerCase(Locale.ROOT), headerValue));

        HttpRequestContext.getInstance().loadHttpRequest(new HttpRequest(
            "POST",
            "/",
            requestHeaders,
            JsonSerializer.getInstance().getJson(postRaw),
            ipAddress,
            query,
            new LinkedHashMap<>(postRaw)
        ));
        return routeParams;
    }

    public void resetAll() {
        bearerToken = "";
        ipAddress = "";
        headers = new LinkedHashMap<>();
        routeParams = new LinkedHashMap<>();
        query = new LinkedHashMap<>();
        postRaw = new LinkedHashMap<>();
    }
}
