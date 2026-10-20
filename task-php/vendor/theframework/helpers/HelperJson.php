<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 1.1.0
 * @name TheFramework\Helpers\HelperJson
 * @date 29-06-2019 15:12 (SPAIN)
 * @file HelperJson.php
 * @observations
 *  https://restfulapi.net/http-status-codes/
 */

namespace TheFramework\Helpers;

final class HelperJson
{
    public const CODE_CONTINUE = 100;
    public const CODE_SWITCHING_PROTOCOLS = 101;
    public const CODE_PROCESSING = 102;

    public const CODE_OK = 200;
    public const CODE_CREATED = 201;
    public const CODE_ACCEPTED = 202;  //para procesos en background
    public const CODE_NON_AUTHORITATIVE_INFORMATION = 203;
    public const CODE_NO_CONTENT = 204;
    public const CODE_RESET_CONTENT = 205;
    public const CODE_PARTIAL_CONTENT = 206;
    public const CODE_MULTI_STATUS = 207;
    public const CODE_ALREADY_REPORTED = 208;
    public const CODE_IM_USED = 226;

    public const CODE_MULTIPLE_CHOICES = 300;
    public const CODE_MOVED_PERMANENTLY = 301;
    public const CODE_FOUND = 302;
    public const CODE_SEE_OTHER = 303;
    public const CODE_NOT_MODIFIED = 304;
    public const CODE_USE_PROXY = 305;
    public const CODE_SWITCH_PROXY = 306;
    public const CODE_TEMPORARY_REDIRECT = 307;
    public const CODE_PERMANENT_REDIRECT = 308;

    public const CODE_BAD_REQUEST = 400;
    public const CODE_UNAUTHORIZED = 401;
    public const CODE_PAYMENT_REQUIRED = 402;
    public const CODE_FORBIDDEN = 403;
    public const CODE_NOT_FOUND = 404;
    public const CODE_METHOD_NOT_ALLOWED = 405;
    public const CODE_NOT_ACCEPTABLE = 406;
    public const CODE_PROXY_AUTHENTICATION_REQUIRED = 407;
    public const CODE_REQUEST_TIMEOUT = 408;
    public const CODE_CONFLICT = 409;
    public const CODE_GONE = 410;
    public const CODE_LENGTH_REQUIRED = 411;
    public const CODE_PRECONDITION_FAILED = 412;
    public const CODE_REQUEST_ENTITY_TOO_LARGE = 413;
    public const CODE_REQUEST_URI_TOO_LONG = 414;
    public const CODE_UNSUPPORTED_MEDIA_TYPE = 415;
    public const CODE_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    public const CODE_EXPECTATION_FAILED = 417;
    public const CODE_IM_A_TEAPOT = 418;
    public const CODE_AUTHENTICATION_TIMEOUT = 419;
    public const CODE_ENHANCE_YOUR_CALM = 420;
    public const CODE_METHOD_FAILURE = 420;
    public const CODE_UNPROCESSABLE_ENTITY = 422;
    public const CODE_LOCKED = 423;
    public const CODE_FAILED_DEPENDENCY = 424;
    public const CODE_UNORDERED_COLLECTION = 425;
    public const CODE_UPGRADE_REQUIRED = 426;
    public const CODE_PRECONDITION_REQUIRED = 428;
    public const CODE_TOO_MANY_REQUESTS = 429;
    public const CODE_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    public const CODE_NO_RESPONSE = 444;
    public const CODE_RETRY_WITH = 449;
    public const CODE_BLOCKED_BY_WINDOWS_PARENTAL_CONTROLS = 450;
    public const CODE_REDIRECT = 451;
    public const CODE_UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    public const CODE_REQUEST_HEADER_TOO_LARGE = 494;
    public const CODE_CERT_ERROR = 495;
    public const CODE_NO_CERT = 496;
    public const CODE_HTTP_TO_HTTPS = 497;
    public const CODE_CLIENT_CLOSED_REQUEST = 499;

    public const CODE_INTERNAL_SERVER_ERROR = 500;
    public const CODE_NOT_IMPLEMENTED = 501;
    public const CODE_BAD_GATEWAY = 502;
    public const CODE_SERVICE_UNAVAILABLE = 503;
    public const CODE_GATEWAY_TIMEOUT = 504;
    public const CODE_HTTP_VERSION_NOT_SUPPORTED = 505;
    public const CODE_VARIANT_ALSO_NEGOTIATES = 506;
    public const CODE_INSUFFICIENT_STORAGE = 507;
    public const CODE_LOOP_DETECTED = 508;
    public const CODE_BANDWIDTH_LIMIT_EXCEEDED = 509;
    public const CODE_NOT_EXTENDED = 510;
    public const CODE_NETWORK_AUTHENTICATION_REQUIRED = 511;
    public const CODE_NETWORK_READ_TIMEOUT_ERROR = 598;
    public const CODE_NETWORK_CONNECT_TIMEOUT_ERROR = 599;

    private array $responseCodes;
    private array $responsePayload;

    public function __construct($payload = [])
    {
        //https://jsonapi.org/format/
        $this->responsePayload["header"]["http"]["code"] = 200;
        $this->responsePayload["header"]["http"]["message"] = "200 ok"; //CODIGO MENSAJE
        //$this->responsePayload["header"]["Allow"] = "GET, HEAD, OPTIONS";
        //$this->responsePayload["header"]["Content-Type"] = "application/json";
        //$this->responsePayload["header"]["Vary"] = "Accept";

        $this->responsePayload["payload"]["status"] = 1;
        $this->responsePayload["payload"]["message"] = "";
        $this->responsePayload["payload"]["links"] = [];
        $this->responsePayload["payload"]["errors"] = [];
        $this->responsePayload["payload"]["data"] = $payload;
        $this->responsePayload["payload"]["included"] = [];
        $this->loadResponseCodes();
    }

    private function loadResponseCodes(): void
    {
        /**
         * Content from http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
         **/
        $this->responseCodes = [
            100 => "Continue",
            101 => "Switching Protocols",
            102 => "Processing", // WebDAV; RFC 2518
            200 => "OK",
            201 => "Created",
            202 => "Accepted",
            203 => "Non-Authoritative Information", // since HTTP/1.1
            204 => "No Content",
            205 => "Reset Content",
            206 => "Partial Content",
            207 => "Multi-Status", // WebDAV; RFC 4918
            208 => "Already Reported", // WebDAV; RFC 5842
            226 => "IM Used", // RFC 3229
            300 => "Multiple Choices",
            301 => "Moved Permanently",
            302 => "Found",
            303 => "See Other", // since HTTP/1.1
            304 => "Not Modified",
            305 => "Use Proxy", // since HTTP/1.1
            306 => "Switch Proxy",
            307 => "Temporary Redirect", // since HTTP/1.1
            308 => "Permanent Redirect", // approved as experimental RFC
            400 => "Bad Request",
            401 => "Unauthorized",
            402 => "Payment Required",
            403 => "Forbidden",
            404 => "Not Found",
            405 => "Method Not Allowed",
            406 => "Not Acceptable",
            407 => "Proxy Authentication Required",
            408 => "Request Timeout",
            409 => "Conflict",
            410 => "Gone",
            411 => "Length Required",
            412 => "Precondition Failed",
            413 => "Request Entity Too Large",
            414 => "Request-URI Too Long",
            415 => "Unsupported Media Type",
            416 => "Requested Range Not Satisfiable",
            417 => "Expectation Failed",
            418 => "I\"m a teapot", // RFC 2324
            419 => "Authentication Timeout", // not in RFC 2616
            420 => "Enhance Your Calm", // Twitter
            420 => "Method Failure", // Spring Framework
            422 => "Unprocessable Entity", // WebDAV; RFC 4918
            423 => "Locked", // WebDAV; RFC 4918
            424 => "Failed Dependency", // WebDAV; RFC 4918
            424 => "Method Failure", // WebDAV)
            425 => "Unordered Collection", // Internet draft
            426 => "Upgrade Required", // RFC 2817
            428 => "Precondition Required", // RFC 6585
            429 => "Too Many Requests", // RFC 6585
            431 => "Request Header Fields Too Large", // RFC 6585
            444 => "No Response", // Nginx
            449 => "Retry With", // Microsoft
            450 => "Blocked by Windows Parental Controls", // Microsoft
            451 => "Redirect", // Microsoft
            451 => "Unavailable For Legal Reasons", // Internet draft
            494 => "Request Header Too Large", // Nginx
            495 => "Cert Error", // Nginx
            496 => "No Cert", // Nginx
            497 => "HTTP to HTTPS", // Nginx
            499 => "Client Closed Request", // Nginx
            500 => "Internal Server Error",
            501 => "Not Implemented",
            502 => "Bad Gateway",
            503 => "Service Unavailable",
            504 => "Gateway Timeout",
            505 => "HTTP Version Not Supported",
            506 => "Variant Also Negotiates", // RFC 2295
            507 => "Insufficient Storage", // WebDAV; RFC 4918
            508 => "Loop Detected", // WebDAV; RFC 5842
            509 => "Bandwidth Limit Exceeded", // Apache bw/limited extension
            510 => "Not Extended", // RFC 2774
            511 => "Network Authentication Required", // RFC 6585
            598 => "Network read timeout error", // Unknown
            599 => "Network connect timeout error", // Unknown
        ];
    }

    private function sendCorsHeaders(): void
    {
        if (!isset($_SERVER["HTTP_ORIGIN"])) {
            return;
        }
        //No 'Access-Control-Allow-Origin' header is present on the requested resource.
        //should do a check here to match $_SERVER["HTTP_ORIGIN"] to a
        //whitelist of safe domains
        header("Access-Control-Allow-Origin: {$_SERVER["HTTP_ORIGIN"]}");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400");// cache for 1 day
        //header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token, Authorization');
    }

    /**
     * Código HTTP configurado (getter puro, sin emitir). Para construir un HttpResponse.
     */
    public function getResponseCode(): int
    {
        return (int) $this->responsePayload["header"]["http"]["code"];
    }

    /**
     * Body JSON serializado (getter puro, sin emitir). Misma serialización que show().
     */
    public function getJsonBody(): string
    {
        return json_encode($this->responsePayload["payload"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function showAndExit(): void
    {
        $this->showAndContinue();
        exit();
    }

    public function showAndContinue(): void
    {
        // clear the old headers
        header_remove();
        $this->sendCorsHeaders();
        // set the actual code
        http_response_code($this->responsePayload["header"]["http"]["code"]);
        // set the header to make sure cache is forced
        header("Cache-Control: no-transform,public,max-age=300,s-maxage=900");
        // treat this as json
        header("Content-Type: application/json");
        // ok, validation error, or failure
        header("Status: {$this->responsePayload["header"]["http"]["message"]}");
        $json = json_encode($this->responsePayload["payload"], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        echo $json;
    }

    public function setPayload(mixed $data): self
    {
        $this->responsePayload["payload"]["data"] = $data;
        return $this;
    }

    public function setLinks(array$links): self
    {
        $this->responsePayload["payload"]["links"] = $links;
        return $this;
    }

    public function setErrors(array $errors): self
    {
        $this->responsePayload["payload"]["errors"] = $errors;
        return $this;
    }

    public function setResponseCode(null |int | string$code): self
    {
        if (!is_numeric($code)) {
            $code = 500;
        }
        $this->responsePayload["payload"]["status"] = ($code < 300);
        $this->responsePayload["payload"]["code"] = $code;

        $this->responsePayload["header"]["http"]["code"] = $code;
        $message = $this->responseCodes[$code] ?? "Unknown";
        $this->responsePayload["header"]["http"]["message"] = "$code $message";
        return $this;
    }

    public function setMessage(?string $message): self
    {
        $this->responsePayload["payload"]["message"] = $message ?? $this->responsePayload["header"]["http"]["message"];
        return $this;
    }

}//HelperJson
