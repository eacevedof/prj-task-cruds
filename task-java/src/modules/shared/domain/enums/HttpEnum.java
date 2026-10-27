package modules.shared.domain.enums;

/**
 * Cabeceras, tipos y claves del envelope REST {code, status, errors, data}: sin literales sueltos en el transporte.
 */
public final class HttpEnum {

    public static final String HEADER_CONTENT_TYPE = "Content-Type";
    public static final String HEADER_WWW_AUTHENTICATE = "WWW-Authenticate";

    public static final String CONTENT_TYPE_JSON = "application/json";
    public static final String CONTENT_TYPE_HTML = "text/html; charset=utf-8";

    public static final String AUTH_SCHEME_BEARER = "Bearer";

    public static final String KEY_CODE = "code";
    public static final String KEY_STATUS = "status";
    public static final String KEY_ERRORS = "errors";
    public static final String KEY_DATA = "data";

    private HttpEnum() {
    }
}
