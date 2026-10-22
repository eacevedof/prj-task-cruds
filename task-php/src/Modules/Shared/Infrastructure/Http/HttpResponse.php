<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Http;

/**
 * Value object de TRANSPORTE HTTP: lo que un controller devuelve y el front controller
 * (IndexMain::exec) emite. NINGUN controller hace echo/header/exit: construye un HttpResponse
 * y lo devuelve (asi los tests pueden asertar getStatusCode() sin emitir nada).
 */
final readonly class HttpResponse
{
    /** @param array<string,string> $headers */
    private function __construct(
        private int    $statusCode,
        private string $body,
        private array  $headers,
    ) {
    }

    /**
     * $body es el JSON ya serializado.
     * @param array<string,string> $headers
     */
    public static function json(
        string $body,
        int $statusCode = 200,
        array $headers = []
    ): self
    {
        return new self(
            $statusCode,
            $body,
            array_merge(["Content-Type" => "application/json"], $headers),
        );
    }

    public static function noContent(int $statusCode = 204): self
    {
        return new self($statusCode, "", []);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    /** @return array<string,string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * UNICO punto de emision de toda la app. Lo llama IndexMain::exec().
     */
    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
        echo $this->body;
    }
}
