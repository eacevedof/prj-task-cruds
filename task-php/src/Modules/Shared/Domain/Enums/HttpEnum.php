<?php
declare(strict_types=1);

namespace App\Modules\Shared\Domain\Enums;

/**
 * Cabeceras, tipos y claves del envelope REST {code, status, errors, data}: sin literales sueltos en el transporte.
 */
abstract class HttpEnum
{
    public const string HEADER_CONTENT_TYPE = "Content-Type";
    public const string HEADER_WWW_AUTHENTICATE = "WWW-Authenticate";

    public const string CONTENT_TYPE_JSON = "application/json";
    public const string CONTENT_TYPE_HTML = "text/html; charset=utf-8";

    public const string AUTH_SCHEME_BEARER = "Bearer";

    public const string KEY_CODE = "code";
    public const string KEY_STATUS = "status";
    public const string KEY_ERRORS = "errors";
    public const string KEY_DATA = "data";
}
