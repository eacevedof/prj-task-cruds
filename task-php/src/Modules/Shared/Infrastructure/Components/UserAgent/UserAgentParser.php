<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Components\UserAgent;

/**
 * Navegador, version y sistema operativo a partir del User-Agent. Deteccion por patrones (sin vendor):
 * suficiente para metadata/trazas, no para decidir permisos (el User-Agent lo escribe el cliente).
 */
final class UserAgentParser
{
    /**
     * El ORDEN importa: Edge y Opera tambien dicen "Chrome", Chrome tambien dice "Safari".
     * nombre => patron cuyo grupo 1 es la version.
     */
    private const array BROWSER_PATTERNS = [
        "Edge" => "/Edg(?:e|A|iOS)?\/([\d.]+)/",
        "Opera" => "/(?:OPR|Opera)\/([\d.]+)/",
        "Firefox" => "/(?:Firefox|FxiOS)\/([\d.]+)/",
        "Chrome" => "/(?:Chrome|CriOS)\/([\d.]+)/",
        "Safari" => "/Version\/([\d.]+).*Safari\//",
        "Postman" => "/PostmanRuntime\/([\d.]+)/",
        "curl" => "/^curl\/([\d.]+)/",
    ];

    /**
     * El ORDEN importa: Android dice "Linux", iPhone/iPad dicen "Mac OS X".
     */
    private const array OS_PATTERNS = [
        "Android" => "/Android/",
        "iOS" => "/iPhone|iPad|iPod/",
        "Windows" => "/Windows/",
        "Mac" => "/Macintosh|Mac OS X/",
        "Ubuntu" => "/Ubuntu/",
        "Linux" => "/Linux/",
    ];

    public static function getInstance(): self
    {
        return new self();
    }

    public function getBrowserName(string $userAgent): string
    {
        foreach (self::BROWSER_PATTERNS as $browserName => $pattern) {
            if (preg_match($pattern, $userAgent)) return $browserName;
        }
        return "";
    }

    public function getBrowserVersion(string $userAgent): string
    {
        foreach (self::BROWSER_PATTERNS as $pattern) {
            if (preg_match($pattern, $userAgent, $matches)) return $matches[1];
        }
        return "";
    }

    public function getOsName(string $userAgent): string
    {
        foreach (self::OS_PATTERNS as $osName => $pattern) {
            if (preg_match($pattern, $userAgent)) return $osName;
        }
        return "";
    }
}
