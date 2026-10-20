<?php

declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Components\HttpResponder;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Domain\Enums\HttpEnum;

/**
 * Value object de TRANSPORTE HTTP: lo que un controller devuelve y el front controller
 * (IndexMain::exec) emite. NINGUN controller hace echo/header/exit: construye un HttpResponder
 * y lo devuelve (asi los tests pueden asertar getStatusCode() sin emitir nada).
 */
final readonly class HttpResponder
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
        int $statusCode = ExceptionEnum::CODE_OK,
        array $headers = []
    ): self
    {
        return new self(
            $statusCode,
            $body,
            array_merge([HttpEnum::HEADER_CONTENT_TYPE => HttpEnum::CONTENT_TYPE_JSON], $headers),
        );
    }

    public static function html(
        string $body,
        int $statusCode = ExceptionEnum::CODE_OK
    ): self
    {
        return new self($statusCode, $body, [HttpEnum::HEADER_CONTENT_TYPE => HttpEnum::CONTENT_TYPE_HTML]);
    }

    public static function noContent(int $statusCode = ExceptionEnum::CODE_NO_CONTENT): self
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
     * Copia con cabeceras comunes (CORS, seguridad) que pone el front controller; si coinciden, gana la del
     * controller.
     * @param array<string,string> $headers
     */
    public function getWithHeaders(array $headers): self
    {
        return new self($this->statusCode, $this->body, array_merge($headers, $this->headers));
    }

    /**
     * UNICO punto de emision de toda la app. Lo llama IndexMain::exec().
     */
    public function echoHeadersAndBody(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $key => $value) {
            header("$key: $value");
        }
        echo $this->body;
    }
}
