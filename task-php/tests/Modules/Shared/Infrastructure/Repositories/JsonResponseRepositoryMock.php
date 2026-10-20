<?php

declare(strict_types=1);

namespace Tests\Modules\Shared\Infrastructure\Repositories;

use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;

/**
 * Lectura del envelope de la API: {"code": int, "status": bool, "errors": string[], "data": array}
 */
final class JsonResponseRepositoryMock
{
    private array $responseArray;

    public function __construct(string $responseContent)
    {
        $this->responseArray = (array) json_decode($responseContent, true);
    }

    public static function fromPrimitives(string $responseContent): self
    {
        return new self($responseContent);
    }

    public static function fromHttpResponse(HttpResponder $httpResponse): self
    {
        return new self($httpResponse->getBody());
    }

    public function getCode(): int
    {
        return (int) ($this->responseArray["code"] ?? 0);
    }

    public function getStatus(): bool
    {
        return (bool) ($this->responseArray["status"] ?? false);
    }

    public function getErrors(): array
    {
        return $this->responseArray["errors"] ?? [];
    }

    public function getData(): array
    {
        return $this->responseArray["data"] ?? [];
    }

    public function getDebug(mixed $useCase = ""): void
    {
        $debug = "";
        if ($useCase) {
            $useCase = is_string($useCase) ? $useCase : $useCase->name ?? "";
            $debug = "useCase: $useCase, ";
        }
        $status = $this->getStatus() ? "true" : "false";
        $debug .= "code: {$this->getCode()}, status: $status, errors: ".implode(" | ", $this->getErrors());
        fwrite(STDERR, $debug.PHP_EOL);
    }

    public function resetAll(): void
    {
        $this->responseArray = [];
    }
}
