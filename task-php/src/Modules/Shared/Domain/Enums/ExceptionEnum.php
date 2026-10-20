<?php

declare(strict_types=1);

namespace App\Modules\Shared\Domain\Enums;

abstract class ExceptionEnum
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
}
