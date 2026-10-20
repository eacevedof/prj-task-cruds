<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Components\HttpRequester;

use App\Modules\Shared\Domain\Enums\LanguageCodeEnum;
use App\Modules\Shared\Infrastructure\Components\UserAgent\UserAgentParser;

/**
 * UNICO punto de lectura de la peticion HTTP ($_POST, $_GET, $_SERVER): body, query, cabeceras y la metadata
 * que viaja con todo dto de entrada (AbstractInputDto). Nadie mas lee las superglobales de la peticion.
 */
final class HttpRequester
{
    private const int USER_AGENT_MAX_LENGTH = 500;

    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * PHP solo rellena $_POST con form-data en POST. La API habla JSON (tambien en PUT/PATCH/DELETE):
     * se decodifica php://input y, si no es un objeto JSON, $_POST queda vacio.
     */
    public function loadJsonBodyIntoPost(): void
    {
        if ($_POST) return;
        if (!$json = (string) file_get_contents("php://input")) return;

        $body = json_decode($json, true);
        $_POST = is_array($body) ? $body : [];
    }

    public function getPost(?string $key = null, mixed $default = null): mixed
    {
        if (!$key) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * Campo escalar del body como string. Un array/objeto en un campo escalar se trata como vacio
     * (y lo rechaza la validacion de campos obligatorios) en vez de dar "Array" o un TypeError.
     */
    public function getPostString(string $key): string
    {
        $value = $this->getPost($key, "");
        return is_scalar($value) ? trim((string) $value) : "";
    }

    public function getGet(?string $key = null, mixed $default = null): mixed
    {
        if (!$key) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    public function getGetString(string $key): string
    {
        $value = $this->getGet($key, "");
        return is_scalar($value) ? trim((string) $value) : "";
    }

    public function getRequestMethod(): string
    {
        return strtolower(trim((string) ($_SERVER["REQUEST_METHOD"] ?? "get")));
    }

    public function getRequestUri(): string
    {
        return (string) ($_SERVER["REQUEST_URI"] ?? "/");
    }

    public function getHeaderValueByKey(string $key): string
    {
        $serverKey = "HTTP_".strtoupper(str_replace("-", "_", $key));
        return trim((string) ($_SERVER[$serverKey] ?? ""));
    }

    public function getBearerToken(): string
    {
        if (!preg_match("/^Bearer\s+(\S+)$/i", $this->getHeaderValueByKey("Authorization"), $matches)) return "";
        return $matches[1];
    }

    public function getMetadata(): array
    {
        $userAgent = $this->getUserAgent();
        $userAgentParser = UserAgentParser::getInstance();
        return [
            "userIpAddress" => $this->getClientIp(),
            "userBrowser" => $userAgentParser->getBrowserName($userAgent),
            "userBrowserVersion" => $userAgentParser->getBrowserVersion($userAgent),
            "userOs" => $userAgentParser->getOsName($userAgent),
            "userTimeZone" => $this->getTimeZone(),
            "userLanguageCode" => $this->getLanguageCode(),
        ];
    }

    public function getUserAgent(): string
    {
        return mb_substr($this->getHeaderValueByKey("User-Agent"), 0, self::USER_AGENT_MAX_LENGTH);
    }


    public function getClientIp(): string
    {
        if ($ip = ($_SERVER["HTTP_CLIENT_IP"] ?? "")) {
            return $ip;
        }
        // "cliente, proxy1, proxy2": el cliente es el primer salto
        if ($ip = trim(explode(",", (string) ($_SERVER["HTTP_X_FORWARDED_FOR"] ?? ""))[0])) {
            return $ip;
        }
        if ($ip = ($_SERVER["REMOTE_ADDR"] ?? "")) {
            return $ip;
        }
        return "127.0.0.1";
    }

    /**
     * Cabecera opcional "X-Time-Zone: Europe/Madrid". Solo si es una zona IANA valida.
     */
    public function getTimeZone(): string
    {
        $timeZone = $this->getHeaderValueByKey("X-Time-Zone");
        return in_array($timeZone, timezone_identifiers_list(), true) ? $timeZone : "";
    }

    /**
     * "es-ES,es;q=0.9,en;q=0.8" -> "es": la primera preferencia, si la API la entiende.
     */
    public function getLanguageCode(): string
    {
        $acceptLanguage = $this->getHeaderValueByKey("Accept-Language");
        $languageCode = strtolower(substr(trim(explode(",", $acceptLanguage)[0]), 0, 2));
        return in_array($languageCode, LanguageCodeEnum::VALUES, true) ? $languageCode : LanguageCodeEnum::EN;
    }
}
