<?php

declare(strict_types=1);

namespace Tests\Modules\Shared\Infrastructure\Components\Http\HttpRequest\Mothers;

use App\Modules\Shared\Domain\Enums\LanguageCodeEnum;

use Tests\Modules\Shared\Infrastructure\Components\HttpRequestMock;

/**
 * Peticiones con cabeceras de navegadores reales y la metadata que se espera de cada una.
 */
final class RequestMetadataRequestMother
{
    private const string IP_ADDRESS = "203.0.113.7";

    private const string UA_CHROME_WINDOWS = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36";
    private const string UA_SAFARI_IPHONE = "Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1";
    private const string UA_EDGE_WINDOWS = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.3537.57";
    private const string UA_CHROME_ANDROID = "Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36";
    private const string UA_CURL = "curl/8.10.1";
    private const string UA_FIREFOX_LINUX = "Mozilla/5.0 (X11; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0";

    public static function getInstance(): self
    {
        return new self();
    }

    public function loadRequestByTestCase(RequestMetadataTestCaseEnum $useCase): void
    {
        $httpRequestMock = HttpRequestMock::getInstance()->setIpAddress(self::IP_ADDRESS);
        match ($useCase) {
            RequestMetadataTestCaseEnum::OK_AS_CHROME_ON_WINDOWS_IN_SPANISH =>
                $httpRequestMock->setHeaders([
                    "User-Agent" => self::UA_CHROME_WINDOWS,
                    "Accept-Language" => "es-ES,es;q=0.9,en;q=0.8",
                    "X-Time-Zone" => "Europe/Madrid",
                ]),

            RequestMetadataTestCaseEnum::OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH =>
                $httpRequestMock->setHeaders([
                    "User-Agent" => self::UA_SAFARI_IPHONE,
                    "Accept-Language" => "en-US,en;q=0.9",
                    "X-Time-Zone" => "America/New_York",
                ]),

            RequestMetadataTestCaseEnum::OK_AS_EDGE_NOT_CHROME_ON_WINDOWS =>
                $httpRequestMock->setHeaders(["User-Agent" => self::UA_EDGE_WINDOWS]),

            RequestMetadataTestCaseEnum::OK_AS_ANDROID_NOT_LINUX =>
                $httpRequestMock->setHeaders(["User-Agent" => self::UA_CHROME_ANDROID]),

            RequestMetadataTestCaseEnum::OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS =>
                $httpRequestMock->setHeaders(["User-Agent" => self::UA_CURL]),

            RequestMetadataTestCaseEnum::OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE =>
                $httpRequestMock->setHeaders([
                    "User-Agent" => self::UA_FIREFOX_LINUX,
                    "Accept-Language" => "fr-FR,fr;q=0.9",
                    "X-Time-Zone" => "Mars/Olympus_Mons",
                ]),
        };
        $httpRequestMock->getMockedInstance();
    }

    /**
     * Metadata esperada, con las claves de AbstractInputDto.
     */
    public function getExpectedMetadataByTestCase(RequestMetadataTestCaseEnum $useCase): array
    {
        return match ($useCase) {
            RequestMetadataTestCaseEnum::OK_AS_CHROME_ON_WINDOWS_IN_SPANISH =>
                $this->getMetadata("Chrome", "141.0.0.0", "Windows", "Europe/Madrid", LanguageCodeEnum::ES),

            RequestMetadataTestCaseEnum::OK_AS_SAFARI_ON_IPHONE_IN_ENGLISH =>
                $this->getMetadata("Safari", "18.5", "iOS", "America/New_York", LanguageCodeEnum::EN),

            RequestMetadataTestCaseEnum::OK_AS_EDGE_NOT_CHROME_ON_WINDOWS =>
                $this->getMetadata("Edge", "141.0.3537.57", "Windows", "", LanguageCodeEnum::EN),

            RequestMetadataTestCaseEnum::OK_AS_ANDROID_NOT_LINUX =>
                $this->getMetadata("Chrome", "141.0.0.0", "Android", "", LanguageCodeEnum::EN),

            RequestMetadataTestCaseEnum::OK_AS_DEFAULTS_BY_CURL_WITHOUT_HEADERS =>
                $this->getMetadata("curl", "8.10.1", "", "", LanguageCodeEnum::EN),

            RequestMetadataTestCaseEnum::OK_AS_DEFAULTS_BY_UNSUPPORTED_LANGUAGE_AND_INVALID_TIME_ZONE =>
                $this->getMetadata("Firefox", "144.0", "Linux", "", LanguageCodeEnum::EN),
        };
    }

    private function getMetadata(
        string $userBrowser,
        string $userBrowserVersion,
        string $userOs,
        string $userTimeZone,
        string $userLanguageCode
    ): array
    {
        return [
            "userIpAddress" => self::IP_ADDRESS,
            "userBrowser" => $userBrowser,
            "userBrowserVersion" => $userBrowserVersion,
            "userOs" => $userOs,
            "userTimeZone" => $userTimeZone,
            "userLanguageCode" => $userLanguageCode,
        ];
    }
}
