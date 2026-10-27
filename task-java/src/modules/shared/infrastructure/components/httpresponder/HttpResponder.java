package modules.shared.infrastructure.components.httpresponder;

import java.io.IOException;
import java.io.OutputStream;
import java.nio.charset.StandardCharsets;
import java.util.LinkedHashMap;
import java.util.Map;

import com.sun.net.httpserver.HttpExchange;

import modules.shared.domain.enums.ExceptionEnum;
import modules.shared.domain.enums.HttpEnum;

/**
 * Value object de TRANSPORTE HTTP: lo que un controller devuelve y el front controller (IndexMain) entrega a
 * cmd/api/Main para que lo emita. NINGUN controller escribe la respuesta: construye un HttpResponder y lo
 * devuelve (asi los tests pueden asertar getStatusCode() sin emitir nada).
 */
public final class HttpResponder {

    private final int statusCode;
    private final String body;
    private final Map<String, String> headers;

    private HttpResponder(int statusCode, String body, Map<String, String> headers) {
        this.statusCode = statusCode;
        this.body = body;
        this.headers = headers;
    }

    public static HttpResponder json(String body) {
        return json(body, ExceptionEnum.CODE_OK, Map.of());
    }

    public static HttpResponder json(String body, int statusCode) {
        return json(body, statusCode, Map.of());
    }

    /**
     * body es el JSON ya serializado.
     */
    public static HttpResponder json(String body, int statusCode, Map<String, String> headers) {
        Map<String, String> jsonHeaders = new LinkedHashMap<>();
        jsonHeaders.put(HttpEnum.HEADER_CONTENT_TYPE, HttpEnum.CONTENT_TYPE_JSON);
        jsonHeaders.putAll(headers);
        return new HttpResponder(statusCode, body, jsonHeaders);
    }

    public static HttpResponder html(String body) {
        return html(body, ExceptionEnum.CODE_OK);
    }

    public static HttpResponder html(String body, int statusCode) {
        Map<String, String> htmlHeaders = new LinkedHashMap<>();
        htmlHeaders.put(HttpEnum.HEADER_CONTENT_TYPE, HttpEnum.CONTENT_TYPE_HTML);
        return new HttpResponder(statusCode, body, htmlHeaders);
    }

    public static HttpResponder noContent() {
        return new HttpResponder(ExceptionEnum.CODE_NO_CONTENT, "", new LinkedHashMap<>());
    }

    public int getStatusCode() {
        return statusCode;
    }

    public String getBody() {
        return body;
    }

    public Map<String, String> getHeaders() {
        return headers;
    }

    /**
     * Copia con cabeceras comunes (CORS, seguridad) que pone el front controller; si coinciden, gana la del
     * controller.
     */
    public HttpResponder getWithHeaders(Map<String, String> commonHeaders) {
        Map<String, String> allHeaders = new LinkedHashMap<>(commonHeaders);
        allHeaders.putAll(headers);
        return new HttpResponder(statusCode, body, allHeaders);
    }

    /**
     * UNICO punto de emision de toda la app. Lo llama cmd/api/Main con lo que devuelve IndexMain.
     * Sin cuerpo (HEAD, 204 o vacio): longitud -1, lo que HttpServer entiende como "no hay body".
     */
    public void echoHeadersAndBody(HttpExchange httpExchange) throws IOException {
        headers.forEach((key, value) -> httpExchange.getResponseHeaders().set(key, value));

        byte[] bodyBytes = body.getBytes(StandardCharsets.UTF_8);
        boolean hasNoBody = bodyBytes.length == 0
            || statusCode == ExceptionEnum.CODE_NO_CONTENT
            || httpExchange.getRequestMethod().equalsIgnoreCase("HEAD");
        httpExchange.sendResponseHeaders(statusCode, hasNoBody ? -1 : bodyBytes.length);
        if (!hasNoBody) {
            try (OutputStream responseBody = httpExchange.getResponseBody()) {
                responseBody.write(bodyBytes);
            }
        }
        httpExchange.close();
    }
}
