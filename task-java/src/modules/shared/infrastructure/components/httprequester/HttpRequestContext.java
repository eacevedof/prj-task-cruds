package modules.shared.infrastructure.components.httprequester;

import java.util.function.Supplier;

/**
 * Donde vive la peticion en curso para que HttpRequester.getInstance() la lea sin que nadie se la pase, como lee
 * PHP sus superglobales. Una por hilo: cada peticion se atiende entera en un hilo de la API, asi que dos peticiones
 * en paralelo no se ven entre si. Fuera de una peticion (consola, un test sin peticion) devuelve una vacia.
 */
public final class HttpRequestContext {

    private static final ThreadLocal<HttpRequest> HTTP_REQUEST_STORAGE = new ThreadLocal<>();

    public static HttpRequestContext getInstance() {
        return new HttpRequestContext();
    }

    /**
     * Lo que se ejecuta en callback (la peticion entera) ve httpRequest; al acabar, el hilo queda como estaba.
     */
    public <T> T runWithHttpRequest(HttpRequest httpRequest, Supplier<T> callback) {
        HttpRequest previousHttpRequest = HTTP_REQUEST_STORAGE.get();
        HTTP_REQUEST_STORAGE.set(httpRequest);
        try {
            return callback.get();
        }
        finally {
            if (previousHttpRequest == null) {
                HTTP_REQUEST_STORAGE.remove();
            }
            else {
                HTTP_REQUEST_STORAGE.set(previousHttpRequest);
            }
        }
    }

    /**
     * Desde aqui hasta que se cargue otra, el hilo ve httpRequest: lo usan los tests (HttpRequestMock), que cargan la
     * peticion simulada como en PHP se rellenan las superglobales.
     */
    public void loadHttpRequest(HttpRequest httpRequest) {
        HTTP_REQUEST_STORAGE.set(httpRequest);
    }

    public HttpRequest getHttpRequest() {
        HttpRequest httpRequest = HTTP_REQUEST_STORAGE.get();
        return httpRequest != null ? httpRequest : getEmptyHttpRequest();
    }

    public HttpRequest getEmptyHttpRequest() {
        return HttpRequest.getEmpty();
    }
}
