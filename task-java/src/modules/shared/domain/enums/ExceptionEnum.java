package modules.shared.domain.enums;

public final class ExceptionEnum {

    public static final int CODE_CONTINUE = 100;
    public static final int CODE_SWITCHING_PROTOCOLS = 101;
    public static final int CODE_PROCESSING = 102;

    public static final int CODE_OK = 200;
    public static final int CODE_CREATED = 201;
    public static final int CODE_ACCEPTED = 202;  //para procesos en background
    public static final int CODE_NON_AUTHORITATIVE_INFORMATION = 203;
    public static final int CODE_NO_CONTENT = 204;
    public static final int CODE_RESET_CONTENT = 205;
    public static final int CODE_PARTIAL_CONTENT = 206;
    public static final int CODE_MULTI_STATUS = 207;
    public static final int CODE_ALREADY_REPORTED = 208;
    public static final int CODE_IM_USED = 226;

    public static final int CODE_MULTIPLE_CHOICES = 300;
    public static final int CODE_MOVED_PERMANENTLY = 301;
    public static final int CODE_FOUND = 302;
    public static final int CODE_SEE_OTHER = 303;
    public static final int CODE_NOT_MODIFIED = 304;
    public static final int CODE_USE_PROXY = 305;
    public static final int CODE_SWITCH_PROXY = 306;
    public static final int CODE_TEMPORARY_REDIRECT = 307;
    public static final int CODE_PERMANENT_REDIRECT = 308;

    public static final int CODE_BAD_REQUEST = 400;
    public static final int CODE_UNAUTHORIZED = 401;
    public static final int CODE_PAYMENT_REQUIRED = 402;
    public static final int CODE_FORBIDDEN = 403;
    public static final int CODE_NOT_FOUND = 404;
    public static final int CODE_METHOD_NOT_ALLOWED = 405;
    public static final int CODE_NOT_ACCEPTABLE = 406;
    public static final int CODE_PROXY_AUTHENTICATION_REQUIRED = 407;
    public static final int CODE_REQUEST_TIMEOUT = 408;
    public static final int CODE_CONFLICT = 409;
    public static final int CODE_GONE = 410;
    public static final int CODE_LENGTH_REQUIRED = 411;
    public static final int CODE_PRECONDITION_FAILED = 412;
    public static final int CODE_REQUEST_ENTITY_TOO_LARGE = 413;
    public static final int CODE_REQUEST_URI_TOO_LONG = 414;
    public static final int CODE_UNSUPPORTED_MEDIA_TYPE = 415;
    public static final int CODE_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    public static final int CODE_EXPECTATION_FAILED = 417;
    public static final int CODE_IM_A_TEAPOT = 418;
    public static final int CODE_AUTHENTICATION_TIMEOUT = 419;
    public static final int CODE_ENHANCE_YOUR_CALM = 420;
    public static final int CODE_METHOD_FAILURE = 420;
    public static final int CODE_UNPROCESSABLE_ENTITY = 422;
    public static final int CODE_LOCKED = 423;
    public static final int CODE_FAILED_DEPENDENCY = 424;
    public static final int CODE_UNORDERED_COLLECTION = 425;
    public static final int CODE_UPGRADE_REQUIRED = 426;
    public static final int CODE_PRECONDITION_REQUIRED = 428;
    public static final int CODE_TOO_MANY_REQUESTS = 429;
    public static final int CODE_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    public static final int CODE_NO_RESPONSE = 444;
    public static final int CODE_RETRY_WITH = 449;
    public static final int CODE_BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS = 450;
    public static final int CODE_REDIRECT = 451;
    public static final int CODE_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    public static final int CODE_REQUEST_HEADER_TOO_LARGE = 494;
    public static final int CODE_CERT_ERROR = 495;
    public static final int CODE_NO_CERT = 496;
    public static final int CODE_HTTP_TO_HTTPS = 497;
    public static final int CODE_CLIENT_CLOSED_REQUEST = 499;

    public static final int CODE_INTERNAL_SERVER_ERROR = 500;
    public static final int CODE_NOT_IMPLEMENTED = 501;
    public static final int CODE_BAD_GATEWAY = 502;
    public static final int CODE_SERVICE_UNAVAILABLE = 503;
    public static final int CODE_GATEWAY_TIMEOUT = 504;
    public static final int CODE_HTTP_VERSION_NOT_SUPPORTED = 505;
    public static final int CODE_VARIANT_ALSO_NEGOTIATES = 506;
    public static final int CODE_INSUFFICIENT_STORAGE = 507;
    public static final int CODE_LOOP_DETECTED = 508;
    public static final int CODE_BANDWIDTH_LIMIT_EXCEEDED = 509;
    public static final int CODE_NOT_EXTENDED = 510;
    public static final int CODE_NETWORK_AUTHENTICATION_REQUIRED = 511;
    public static final int CODE_NETWORK_READ_TIMEOUT_ERROR = 598;
    public static final int CODE_NETWORK_CONNECT_TIMEOUT_ERROR = 599;

    private ExceptionEnum() {
    }
}
