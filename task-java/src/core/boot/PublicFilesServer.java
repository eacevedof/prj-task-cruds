package core.boot;

import java.io.IOException;
import java.io.OutputStream;
import java.net.URLDecoder;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.Arrays;
import java.util.Locale;
import java.util.Map;

import com.sun.net.httpserver.HttpExchange;

/**
 * Lo que en task-php hace nginx: si la url es un fichero de public/ se sirve tal cual; si no, pasa al front
 * controller (try_files $uri @rewriteapp). Los ficheros ocultos (.env, .git...) nunca: 404.
 */
public final class PublicFilesServer {

    // tipos de los ficheros de public/ (los que hay)
    private static final Map<String, String> CONTENT_TYPES = Map.of(
        ".css", "text/css; charset=utf-8",
        ".js", "text/javascript; charset=utf-8",
        ".html", "text/html; charset=utf-8",
        ".json", "application/json",
        ".txt", "text/plain; charset=utf-8",
        ".svg", "image/svg+xml",
        ".png", "image/png",
        ".jpg", "image/jpeg",
        ".ico", "image/vnd.microsoft.icon",
        ".woff2", "font/woff2"
    );

    public static PublicFilesServer getInstance() {
        return new PublicFilesServer();
    }

    /**
     * true si la peticion era suya y ya la ha respondido; false si la tiene que atender IndexMain.
     */
    public boolean isPublicFileServed(String requestUri, HttpExchange httpExchange) throws IOException {
        String urlPath = requestUri.split("[?#]", 2)[0];
        if (urlPath.contains("/.")) {
            sendResponse(httpExchange, 404, "text/plain; charset=utf-8", "404 page not found\n".getBytes(StandardCharsets.UTF_8));
            return true;
        }

        Path pathFile = getPathPublicFile(urlPath);
        if (pathFile == null) {
            return false;
        }

        String fileName = pathFile.getFileName().toString();
        int dotIndex = fileName.lastIndexOf('.');
        String extension = dotIndex < 0 ? "" : fileName.substring(dotIndex).toLowerCase(Locale.ROOT);
        sendResponse(httpExchange, 200, CONTENT_TYPES.getOrDefault(extension, "application/octet-stream"), Files.readAllBytes(pathFile));
        return true;
    }

    /**
     * La ruta del fichero dentro de public/, o null si no existe, no es un fichero o se sale de public/.
     */
    private Path getPathPublicFile(String urlPath) {
        String relativePath;
        try {
            relativePath = URLDecoder.decode(urlPath.replace("+", "%2B"), StandardCharsets.UTF_8).replaceAll("^/+", "");
        }
        catch (IllegalArgumentException e) {
            return null;
        }
        if (relativePath.isEmpty() || Arrays.asList(relativePath.replace("\\", "/").split("/")).contains("..")) {
            return null;
        }

        Path pathFile = Path.of(BootPathsEnum.PATH_PUBLIC, relativePath);
        return Files.isRegularFile(pathFile) ? pathFile : null;
    }

    private void sendResponse(HttpExchange httpExchange, int statusCode, String contentType, byte[] body) throws IOException {
        httpExchange.getResponseHeaders().set("Content-Type", contentType);
        boolean isHead = httpExchange.getRequestMethod().equalsIgnoreCase("HEAD");
        httpExchange.sendResponseHeaders(statusCode, isHead ? -1 : body.length);
        if (!isHead) {
            try (OutputStream responseBody = httpExchange.getResponseBody()) {
                responseBody.write(body);
            }
        }
        httpExchange.close();
    }
}
