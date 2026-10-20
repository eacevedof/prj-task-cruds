<?php

declare(strict_types=1);

namespace Tests\Modules\Shared\Infrastructure\Components\Http\HttpRequest\Mothers;

enum RequestMetadataTestCaseEnum
{
    case OK_AS_CHROME_ON_WINDOWS_IN_SPANISH;
    case OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH;
    case OK_AS_EDGE_NOT_CHROME_ON_WINDOWS;
    case OK_AS_ANDROID_NOT_LINUX;
    case OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS;
    case OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE;
}
