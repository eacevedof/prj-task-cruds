package cmd.api;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.net.InetSocketAddress;
import java.net.URLDecoder;
import java.nio.charset.StandardCharsets;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.concurrent.Executors;

import com.sun.net.httpserver.HttpExchange;
import com.sun.net.httpserver.HttpServer;

import core.boot.IndexMain;
import core.boot.PublicFilesServer;
import core.config.Global;

import modules.shared.infrastructure.components.httprequester.HttpRequest;
import modules.shared.infrastructure.components.httprequester.HttpRequestContext;
import modules.shared.infrastructure.components.httpresponder.HttpResponder;

/**
 * API de task-java: el public/index.php de task-php. bin/api --addr=0.0.0.0:8080
 * Cada peticion se atiende entera en un hilo del pool dentro de su HttpRequestContext: lo que en PHP son las
 * superglobales de esa peticion.
 */
public final class Main {

    private static final String DEFAULT_LISTEN_ADDRESS = "0.0.0.0:8080";
    // peticiones atendidas a la vez (cada hilo con su conexion SQLite: DbFactory)
    private static final int HTTP_THREADS = 8;
    // el body mas grande que se lee (el post_max_size de PHP): uno mayor llega vacio
    private static final int MAX_BODY_BYTES = 8 * 1024 * 1024;

    private Main() {
    }

    public static void main(String[] args) throws IOException {
        try {
            Global.appboot_loadenv();
        }
        catch (Exception e) {
            System.err.println(e.getMessage());
            System.exit(1);
        }

        String[] listenAddress = getListenAddress(args).split(":");
        String host = listenAddress[0];
        int port = Integer.parseInt(listenAddress[1]);

        HttpServer httpServer = HttpServer.create(new InetSocketAddress(host, port), 0);
        httpServer.createContext("/", Main::serveRequest);
        httpServer.setExecutor(Executors.newFixedThreadPool(HTTP_THREADS));
        httpServer.start();
        System.out.println("task-java api listening on " + host + ":" + port);
    }

    /**
     * --addr=host:puerto (por defecto 0.0.0.0:8080)
     */
    private static String getListenAddress(String[] args) {
        for (String argument : args) {
            if (argument.startsWith("--addr=")) {
                return argument.substring("--addr=".length());
            }
        }
        return DEFAULT_LISTEN_ADDRESS;
    }

    /**
     * Los ficheros de public/ los sirve PublicFilesServer (lo que hace nginx en task-php); el resto, IndexMain.
     * Un cliente que corta a mitad no tiene a quien responder: la conexion se cierra.
     */
    private static void serveRequest(HttpExchange httpExchange) {
        try {
            HttpRequest httpRequest = getHttpRequest(httpExchange);
            if (PublicFilesServer.getInstance().isPublicFileServed(httpRequest.getRequestUri(), httpExchange)) {
                return;
            }

            HttpResponder httpResponder = HttpRequestContext.getInstance().runWithHttpRequest(httpRequest, () -> {
                try {
                    return IndexMain.getInstance().exec();
                }
                catch (Throwable throwable) {
                    IndexMain.debug(throwable);
                    return IndexMain.onError(throwable);
                }
            });
            httpResponder.echoHeadersAndBody(httpExchange);
        }
        catch (IOException e) {
            httpExchange.close();
        }
    }

    /**
     * La peticion de HttpServer como HttpRequest: lo que en PHP son las superglobales.
     */
    private static HttpRequest getHttpRequest(HttpExchange httpExchange) throws IOException {
        String rawQuery = httpExchange.getRequestURI().getRawQuery();
        String requestUri = httpExchange.getRequestURI().getRawPath() + (rawQuery == null ? "" : "?" + rawQuery);

        Map<String, String> headers = new LinkedHashMap<>();
        httpExchange.getRequestHeaders().forEach(
            (name, values) -> headers.put(name.toLowerCase(Locale.ROOT), String.join(", ", values))
        );

        // una ipv4 por un socket ipv6 llega como "::ffff:172.18.0.1"
        String remoteAddr = httpExchange.getRemoteAddress().getAddress().getHostAddress().replaceFirst("^::ffff:", "");

        return new HttpRequest(
            httpExchange.getRequestMethod(),
            requestUri,
            headers,
            getBody(httpExchange.getRequestBody()),
            remoteAddr,
            getQueryParams(rawQuery),
            new LinkedHashMap<>()
        );
    }

    private static String getBody(InputStream requestBody) throws IOException {
        ByteArrayOutputStream body = new ByteArrayOutputStream();
        byte[] buffer = new byte[8192];
        long bodyBytes = 0;
        int read;
        while ((read = requestBody.read(buffer)) != -1) {
            bodyBytes += read;
            if (bodyBytes <= MAX_BODY_BYTES) body.write(buffer, 0, read);
        }
        return bodyBytes <= MAX_BODY_BYTES ? body.toString(StandardCharsets.UTF_8) : "";
    }

    /**
     * $_GET: la query string decodificada (si una clave se repite, gana la ultima).
     */
    private static Map<String, Object> getQueryParams(String rawQuery) {
        Map<String, Object> queryParams = new LinkedHashMap<>();
        if (rawQuery == null || rawQuery.isEmpty()) return queryParams;

        for (String pair : List.of(rawQuery.split("&"))) {
            if (pair.isEmpty()) continue;
            int separatorIndex = pair.indexOf('=');
            String key = separatorIndex < 0 ? pair : pair.substring(0, separatorIndex);
            String value = separatorIndex < 0 ? "" : pair.substring(separatorIndex + 1);
            try {
                queryParams.put(URLDecoder.decode(key, StandardCharsets.UTF_8), URLDecoder.decode(value, StandardCharsets.UTF_8));
            }
            catch (IllegalArgumentException e) {
                queryParams.put(key, value);
            }
        }
        return queryParams;
    }
}
