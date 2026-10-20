<?php

declare(strict_types=1);

namespace Tests\Modules\Shared\Infrastructure\Components;

/**
 * Peticion simulada. No hay objeto Request: cabeceras e ip van en $_SERVER (el Bearer que lee getAuthUserOrFail,
 * User-Agent / Accept-Language / X-Time-Zone que lee HttpRequester), IndexMain deja el body JSON en
 * $_POST, la query en $_GET y los tags de la url (":userUuid") llegan como argumentos con nombre al metodo
 * del controller. getMockedInstance() carga todo eso y devuelve esos argumentos: $controller->show(...$routeArgs)
 */
final class HttpRequestMock
{
    /**
     * Claves de $_SERVER que puede tocar: getMockedInstance() las limpia antes de cargar las del caso.
     */
    public const array SERVER_KEYS = [
        "HTTP_AUTHORIZATION", "HTTP_USER_AGENT", "HTTP_ACCEPT_LANGUAGE", "HTTP_X_TIME_ZONE", "REMOTE_ADDR",
    ];

    private string $bearerToken = "";
    private string $ipAddress = "";
    private array $headers = [];
    private array $routeParams = [];
    private array $query = [];
    private array $postRaw = [];

    public static function getInstance(): self
    {
        return new self();
    }

    public function setBearerToken(string $bearerToken): self
    {
        $this->bearerToken = $bearerToken;
        return $this;
    }

    public function setIpAddress(string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    /**
     * Cabeceras con su nombre HTTP: ["User-Agent" => "...", "Accept-Language" => "es-ES"].
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    public function setRouteParams(array $routeParams): self
    {
        $this->routeParams = $routeParams;
        return $this;
    }

    public function setGetParams(array $queryParams): self
    {
        $this->query = $queryParams;
        return $this;
    }

    public function setPostRawParams(array $postParams): self
    {
        $this->postRaw = $postParams;
        return $this;
    }

    /**
     * @return array<string, string> argumentos con nombre del metodo del controller
     */
    public function getMockedInstance(): array
    {
        $_GET = $this->query;
        $_POST = $this->postRaw;
        foreach (self::SERVER_KEYS as $serverKey) {
            unset($_SERVER[$serverKey]);
        }
        if ($this->bearerToken) {
            $_SERVER["HTTP_AUTHORIZATION"] = "Bearer {$this->bearerToken}";
        }
        if ($this->ipAddress) {
            $_SERVER["REMOTE_ADDR"] = $this->ipAddress;
        }
        foreach ($this->headers as $headerName => $headerValue) {
            $_SERVER["HTTP_".strtoupper(str_replace("-", "_", $headerName))] = $headerValue;
        }
        return $this->routeParams;
    }

    public function resetAll(): void
    {
        $this->bearerToken = "";
        $this->ipAddress = "";
        $this->headers = [];
        $this->routeParams = [];
        $this->query = [];
        $this->postRaw = [];
    }
}
