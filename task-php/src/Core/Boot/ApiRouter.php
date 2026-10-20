<?php

declare(strict_types=1);

namespace App\Core\Boot;

/**
 * Router REST: casa por METODO + patron de url (TheFramework ComponentRouter solo mira la url, asi
 * que GET y POST sobre /users chocaban, y si no casaba nada devolvia la ultima ruta).
 * - url con tags ":nombre" -> se pasan al metodo del controller como argumentos con nombre.
 * - getRunData() devuelve la ruta + "_args"; si la url casa pero el metodo no -> ["allowed" => [...]]
 *   (405); si no casa ninguna url -> [] (404).
 */
final class ApiRouter
{
    public function __construct(
        private readonly array $routes
    ) {
    }

    public static function getInstance(array $routes): self
    {
        return new self($routes);
    }

    public function getRunData(string $requestMethod, string $requestUri): array
    {
        $requestMethod = strtolower($requestMethod);
        //RFC 9110: todo recurso que admite GET admite HEAD
        if ($requestMethod === "head") {
            $requestMethod = "get";
        }
        $path = $this->getPathWithoutQueryString($requestUri);

        $allowedForPath = [];
        foreach ($this->routes as $route) {
            $args = $this->getArgsIfPathMatches($route["url"], $path);
            if ($args === null) continue;

            $allowed = $route["allowed"] ?? ["get"];
            if (in_array($requestMethod, $allowed, true)) {
                return array_merge($route, ["_args" => $args]);
            }
            $allowedForPath = array_merge($allowedForPath, $allowed);
        }

        return $allowedForPath
            ? ["allowed" => array_values(array_unique($allowedForPath))]
            : [];
    }

    private function getPathWithoutQueryString(string $requestUri): string
    {
        $path = (string) parse_url($requestUri, PHP_URL_PATH);
        $path = rtrim($path, "/");
        return $path ?: "/";
    }

    private function getArgsIfPathMatches(string $routeUrl, string $path): ?array
    {
        $pattern = preg_replace("#:([a-zA-Z_][a-zA-Z0-9_]*)#", "(?P<$1>[^/]+)", $routeUrl);
        if (!preg_match("#^{$pattern}$#", $path, $matches)) {
            return null;
        }

        $args = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $args[$key] = trim(urldecode($value));
            }
        }
        return $args;
    }
}
