<?php

declare(strict_types=1);

namespace Tests\Modules\GraphQl\Infrastructure\Repositories;

use App\Modules\GraphQl\Domain\Enums\GraphQlEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;

/**
 * Lectura de una respuesta GraphQL: {"data": {...}, "errors": [{"message", "extensions": {"code", "errors"}}]}
 */
final class GraphQlResponseRepositoryMock
{
    private array $responseArray;

    public function __construct(string $responseContent)
    {
        $this->responseArray = (array) json_decode($responseContent, true);
    }

    public static function fromHttpResponse(HttpResponder $httpResponse): self
    {
        return new self($httpResponse->getBody());
    }

    public function getData(): array
    {
        return $this->responseArray["data"] ?? [];
    }

    public function getErrors(): array
    {
        return $this->responseArray[GraphQlEnum::KEY_ERRORS] ?? [];
    }

    /**
     * Codigo HTTP equivalente REST del primer error (0 si es un error de GraphQL, sin extensions).
     */
    public function getFirstErrorCode(): int
    {
        return (int) ($this->getErrors()[0]["extensions"][GraphQlEnum::EXTENSION_CODE] ?? 0);
    }

    public function getFirstErrorDetails(): array
    {
        return $this->getErrors()[0]["extensions"][GraphQlEnum::EXTENSION_ERRORS] ?? [];
    }
}
