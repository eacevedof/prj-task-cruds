package core.boot;

import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;

import core.boot.ApiRouter.Route;
import core.boot.ApiRouter.RunData;
import core.routes.Routes;

import modules.shared.application.Primitives;
import modules.shared.domain.enums.ExceptionEnum;
import modules.shared.infrastructure.components.httprequester.HttpRequester;
import modules.shared.infrastructure.components.httpresponder.HttpResponder;
import modules.shared.infrastructure.components.json.JsonSerializer;
import modules.shared.infrastructure.components.uuid.UuidGenerator;
import modules.shared.infrastructure.exceptions.InterfaceException;
import modules.shared.infrastructure.repositories.configuration.EnvironmentReaderRawRepository;

import static core.config.Global.lgerr;

/**
 * Front controller de la API (el public/index.php de task-php). Sin sesion ni vistas: solo JSON.
 * Devuelve la respuesta en vez de emitirla: la emite cmd/api/Main (HttpResponder.echoHeadersAndBody).
 */
public final class IndexMain {

    private final HttpRequester httpRequest;
    private final List<Route> routes;

    private IndexMain() {
        this.httpRequest = HttpRequester.getInstance();
        this.routes = Routes.getRoutes();
    }

    public static IndexMain getInstance() {
        return new IndexMain();
    }

    public HttpResponder exec() {
        String requestMethod = httpRequest.getRequestMethod();
        if (requestMethod.equals("options")) {
            return getWithCommonHeaders(HttpResponder.noContent());
        }

        RunData runData = ApiRouter.getInstance(routes).getRunData(requestMethod, httpRequest.getRequestUri());

        if (runData == null) {
            return getWithCommonHeaders(getErrorResponse(
                ExceptionEnum.CODE_NOT_FOUND,
                "Resource not found",
                Map.of()
            ));
        }

        if (runData.route() == null) {
            return getWithCommonHeaders(getErrorResponse(
                ExceptionEnum.CODE_METHOD_NOT_ALLOWED,
                "Method not allowed",
                Map.of("Allow", String.join(", ", runData.allowed()).toUpperCase(Locale.ROOT))
            ));
        }

        httpRequest.loadJsonBodyIntoPost();

        HttpResponder httpResponse = runData.route().controller().apply(runData.args());
        return getWithCommonHeaders(httpResponse);
    }

    /**
     * El error que nadie ha capturado acaba aqui: se loguea con un id y el cliente recibe ese id en un 500 generico.
     */
    public static HttpResponder onError(Throwable ex) {
        String uuid = UuidGenerator.getInstance().getUniqueIdWithPrefix("");
        SensitiveDataMasker sensitiveDataMasker = SensitiveDataMasker.getInstance();
        HttpRequester httpRequest = HttpRequester.getInstance();
        Map<String, Object> post = httpRequest.getPost();
        if (!post.isEmpty()) {
            lgerr(sensitiveDataMasker.getMaskedArray(post), "index-exception " + uuid + " POST", "error");
        }
        Map<String, Object> get = httpRequest.getGet();
        if (!get.isEmpty()) {
            lgerr(sensitiveDataMasker.getMaskedArray(get), "index-exception " + uuid + " GET", "error");
        }
        lgerr(ex.getMessage(), "index-exception " + uuid, "error");
        lgerr(InterfaceException.getThrowableTraceAsString(ex), "index-exception " + uuid + " TRACE", "error");
        lgerr(
            InterfaceException.getThrowableFile(ex) + " : (line: " + InterfaceException.getThrowableLine(ex) + ")",
            "file-line " + uuid,
            "error"
        );

        return getWithCommonHeaders(HttpResponder.json(
            JsonSerializer.getInstance().getJson(Primitives.of(
                "code", ExceptionEnum.CODE_INTERNAL_SERVER_ERROR,
                "status", false,
                "errors", List.of("Sorry! but some unexpected error occurred (" + uuid + ")"),
                "data", List.of()
            )),
            ExceptionEnum.CODE_INTERNAL_SERVER_ERROR
        ));
    }

    public static void debug(Throwable ex) {
        EnvironmentReaderRawRepository environmentReaderRawRepository = EnvironmentReaderRawRepository.getInstance();
        if (environmentReaderRawRepository.isProduction()) return;
        if (!environmentReaderRawRepository.isDebug()) return;

        lgerr(Primitives.of(
            "Exception", ex.getMessage(),
            "File", InterfaceException.getThrowableFile(ex) + "(" + InterfaceException.getThrowableLine(ex) + ")",
            "POST", HttpRequester.getInstance().getPost(),
            "GET", HttpRequester.getInstance().getGet()
        ), "index-debug", "debug");
    }

    /**
     * Toda respuesta sale por aqui: las cabeceras comunes se suman a las del HttpResponder y se emiten juntas
     * en echoHeadersAndBody(), el unico punto que escribe la respuesta.
     */
    private static HttpResponder getWithCommonHeaders(HttpResponder httpResponse) {
        Map<String, String> commonHeaders = new LinkedHashMap<>(getCorsHeaders());
        commonHeaders.putAll(getSecurityHeaders());
        return httpResponse.getWithHeaders(commonHeaders);
    }

    private static Map<String, String> getCorsHeaders() {
        HttpRequester httpRequest = HttpRequester.getInstance();
        Map<String, String> corsHeaders = new LinkedHashMap<>();
        String origin = httpRequest.getHeaderValueByKey("Origin");
        if (!origin.isEmpty()) {
            corsHeaders.put("Access-Control-Allow-Origin", origin);
            corsHeaders.put("Access-Control-Max-Age", "86400");
        }
        if (httpRequest.getRequestMethod().equals("options")) {
            corsHeaders.put("Access-Control-Allow-Methods", "GET, POST, PUT, DELETE, OPTIONS");
            corsHeaders.put("Access-Control-Allow-Headers", "Content-Type, Accept, Accept-Language, Authorization, X-Time-Zone");
        }
        return corsHeaders;
    }

    private static Map<String, String> getSecurityHeaders() {
        Map<String, String> securityHeaders = new LinkedHashMap<>();
        securityHeaders.put("X-Content-Type-Options", "nosniff");
        securityHeaders.put("Referrer-Policy", "no-referrer");
        return securityHeaders;
    }

    private HttpResponder getErrorResponse(int code, String error, Map<String, String> headers) {
        return HttpResponder.json(
            JsonSerializer.getInstance().getJson(Primitives.of(
                "code", code,
                "status", false,
                "errors", List.of(error),
                "data", List.of()
            )),
            code,
            headers
        );
    }
}
