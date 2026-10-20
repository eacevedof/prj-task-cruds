<?php

declare(strict_types=1);

/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @name TheApplication\Components\ComponentRouter
 * @file ComponentRouter.php 1.0.0
 * @date 30-07-2022 08:19 SPAIN
 * @observations
 */

namespace TheFramework\Components;

final class ComponentRouter
{
    private string $requestUri;
    private string $pathRoutesFile;
    private array $routesArray;
    private array $requestArray;
    private array $argumentsArray;

    private static array $routes;

    public function __construct(
        array $routesArray = [],
        string $pathRoutesFile = ""
    )
    {
        $this->requestUri = $_SERVER["REQUEST_URI"];
        $this->pathRoutesFile = $pathRoutesFile;
        $this->routesArray = $routesArray;
        self::$routes = $routesArray;

        $this->requestArray = [
            "url" => "",
            "url_pieces" => [],
            "get_params" => []
        ];
        $this->loadRoutesArray();
        $this->loadRequestArrayPiecesAndParams();
    }

    private function loadRoutesArray(): void
    {
        if ($this->routesArray || !$this->pathRoutesFile) {
            return;
        }
        $this->routesArray = include($this->pathRoutesFile);
        self::$routes = $this->routesArray;
    }

    private function loadRequestArrayPiecesAndParams(): void
    {
        $getParams = $this->getGETParamsFromRequestUri($this->requestUri);
        $urlPieces = $this->getUrlPiecesWithoutQueryString($this->requestUri);
        $this->requestArray["url"] = "/".implode("/", $urlPieces);
        $this->requestArray["url_pieces"] = $urlPieces;
        $this->requestArray["get_params"] = $getParams;
    }

    private function getRouteByExactPieces(): array
    {
        $requri = $this->requestArray["url"];
        $routes = array_filter($this->routesArray, function ($route) use ($requri) {
            return $route["url"] === $requri;
        });
        return reset($routes) ?: [];
    }

    private function getRouteByPieces(): array
    {
        $allPiecesFound = false;
        foreach($this->routesArray as $i => $route) {
            $url = $route["url"];
            $routesArrayep = $this->getUrlPiecesWithQueryString($url);
            $this->argumentsArray = [];
            //compare pieces comprueba todo, tammaño y tags
            $allPiecesFound = $this->areAllPiecesFoundAndLoadedIntoArumentArray($this->requestArray["url_pieces"], $routesArrayep);
            if ($allPiecesFound) {
                break;
            }
        }

        if ($allPiecesFound) {
            $this->addToGlobalGETVarEachTag($this->requestArray["url_pieces"], $routesArrayep);
        }

        $r = array_merge(
            $this->routesArray[$i],
            $this->argumentsArray ? ["_args" => $this->argumentsArray] : []
        );
        return $r;
    }

    public function getRundata(): array
    {
        return $this->getRouteByExactPieces() ?: $this->getRouteByPieces();
    }

    private function doesRouteHaveNullableVars(array $route): bool
    {
        $null = array_filter(
            array_values($route),
            function ($string) {
                return (strstr($string, "?:") || strstr($string, "?int:"));
            }
        );
        return (bool) count($null);
    }

    private function doRequestAndRouteMatchInNumberOfPieces(array $requestPieces, array $routePieces): bool
    {
        if (($numReqPieces = count($requestPieces)) === ($numRoutePieces = count($routePieces))) {
            return true;
        }
        //esto no está del todo fino ya que no se permitiria varios ?: como partes de la ruta
        if ($this->doesRouteHaveNullableVars($routePieces) && $numReqPieces === ($numRoutePieces - 1)) {
            return true;
        }
        return false;
    }

    private function areAllPiecesFoundAndLoadedIntoArumentArray(array $requestArray, array $route): bool
    {
        //true si casan todas las partes y de paso carga los args
        if (!$this->doRequestAndRouteMatchInNumberOfPieces($requestArray, $route)) {
            return false;
        }

        foreach($route as $i => $piece) {
            if ($this->doesHaveUrlPieceHaveSomeVarArgument($piece)) {
                $tag = $this->getVarArgumentInfo($piece);
                $value = $requestArray[$i] ?? null;
                if (!$this->doesValueIsInTypes($value, $tag["types"])) {
                    return false;
                }
                $this->argumentsArray[$tag["key"]] = trim(urldecode($value ?? ""));
                continue;
            }
            $reqval = $requestArray[$i];
            if ($reqval != $piece) {
                return false;
            }
        }
        return true;
    }

    private function addToGlobalGETVarEachTag(array $requestArray, array $route): void
    {
        foreach($route as $i => $piece) {
            if (!$this->doesHaveUrlPieceHaveSomeVarArgument($piece)) {
                continue;
            }
            $tag = $this->getVarArgumentInfo($piece);
            $_GET[$tag["key"]] = $requestArray[$i] ?? "";
        }
    }

    private function doesHaveUrlPieceHaveSomeVarArgument(string $piece): bool
    {
        return (
            (strstr($piece, "{") && strstr($piece, "}")) ||
            strstr($piece, ":") ||
            strstr($piece, "?:")
        );
    }

    private function doesValueIsInTypes(mixed $anyValue, array $types): bool
    {
        foreach ($types as $type) {
            if ($type === "int" && is_numeric($anyValue)) {
                return true;
            }
            if ($type === "string" && is_string($anyValue)) {
                return true;
            }
            if ($type === "null" && $anyValue === null) {
                return true;
            }
        }
        return false;
    }

    private function getVarArgumentInfo(string $piece): array
    {
        //restrict/users/:page
        //restrict/users/int:page
        //restrict/users/?:page
        //restrict/users/?int:page

        $parts = explode(":", $piece);
        $r = [
            "types" => ["string"],
            "key" => $parts[1]
        ];
        $before = $parts[0];
        switch ($before) {
            case "": return $r;
            case "?":
                $r["types"][] = "null";
                break;
            case "?int":
                $r["types"] = ["null", "int"];
                break;
            case "int":
                $r["types"] = ["int"];
        }
        return $r;
    }

    private function getGETQueryStringAsArray(string $queryString): array
    {
        $ret = [];
        $tmp = explode("&", $queryString);
        foreach($tmp as $eq) {
            $paramVal = explode("=", $eq);
            $ret[$paramVal[0]] = isset($paramVal[1]) ? $paramVal[1] : "";
        }
        return $ret;
    }

    private function getGETParamsFromRequestUri(string $url): array
    {
        $tmp = explode("?", $url);
        if (!isset($tmp[1])) {
            return [];
        }
        $params = $this->getGETQueryStringAsArray($tmp[1]);
        return $params;
    }

    private function removeItemsWithEmptyValue(array &$urlPieces): void
    {
        $new = [];
        foreach($urlPieces as $i => $value) {
            if ($value) {
                $new[] = $value;
            }
        }
        $urlPieces = $new;
    }

    private function getUrlPiecesWithoutQueryString(string $url): array
    {
        $url = explode("?", $url)[0];
        return $this->getUrlPiecesWithQueryString($url);
    }

    private function getUrlPiecesWithQueryString(string $url): array
    {
        $urlPieces = explode("/", $url);
        $this->removeItemsWithEmptyValue($urlPieces);
        return $urlPieces;
    }

    public static function getUrl(string $name, array $args = []): string
    {
        $route = array_filter(self::$routes, function (array $route) use ($name) {
            if (!$alias = ($route["name"] ?? "")) {
                return false;
            }
            return trim($alias) === $name;
        });
        if (!$route) {
            return "";
        }
        $route = array_values($route);
        $url = $route[0]["url"];
        if (!$args) {
            return $url;
        }
        $tags = array_keys($args);
        $tags = array_map(function (string $tag) {
            return str_starts_with($tag, ":") ? $tag : ":$tag";
        }, $tags);

        $values = array_values($args);
        return str_replace($tags, $values, $url);
    }

}
