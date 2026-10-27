package modules.shared.infrastructure.components.httprequester;

import java.util.LinkedHashMap;
import java.util.Map;

/**
 * La peticion HTTP en curso: lo que en PHP son las superglobales ($_SERVER, $_GET, $_POST). Las cabeceras van en
 * minusculas. post empieza vacio: lo rellena HttpRequester.loadJsonBodyIntoPost().
 */
public final class HttpRequest {

    private final String method;
    private final String requestUri;
    private final Map<String, String> headers;
    private final String body;
    private final String remoteAddr;
    private final Map<String, Object> get;
    private Map<String, Object> post;

    public HttpRequest(
        String method,
        String requestUri,
        Map<String, String> headers,
        String body,
        String remoteAddr,
        Map<String, Object> get,
        Map<String, Object> post
    ) {
        this.method = method;
        this.requestUri = requestUri;
        this.headers = headers;
        this.body = body;
        this.remoteAddr = remoteAddr;
        this.get = get;
        this.post = post;
    }

    public static HttpRequest getEmpty() {
        return new HttpRequest("", "", new LinkedHashMap<>(), "", "", new LinkedHashMap<>(), new LinkedHashMap<>());
    }

    public String getMethod() {
        return method;
    }

    public String getRequestUri() {
        return requestUri;
    }

    public Map<String, String> getHeaders() {
        return headers;
    }

    public String getBody() {
        return body;
    }

    public String getRemoteAddr() {
        return remoteAddr;
    }

    public Map<String, Object> getGet() {
        return get;
    }

    public Map<String, Object> getPost() {
        return post;
    }

    public void setPost(Map<String, Object> post) {
        this.post = post;
    }
}
