<?php

declare(strict_types=1);

namespace Tests\Modules\Shared\Infrastructure\Components;

/**
 * Peticion simulada. No hay objeto Request: IndexMain deja el body JSON en $_POST, la query en $_GET y
 * los tags de la url (":userUuid") llegan como argumentos con nombre al metodo del controller.
 * getMockedInstance() carga $_GET/$_POST y devuelve esos argumentos: $controller->show(...$routeArgs)
 */
final class HttpRequestMock
{
    private array $routeParams = [];
    private array $query = [];
    private array $postRaw = [];

    public static function getInstance(): self
    {
        return new self();
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
        return $this->routeParams;
    }

    public function resetAll(): void
    {
        $this->routeParams = [];
        $this->query = [];
        $this->postRaw = [];
    }
}
