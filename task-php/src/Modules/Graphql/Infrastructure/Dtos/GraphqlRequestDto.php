<?php
declare(strict_types=1);

namespace App\Modules\Graphql\Infrastructure\Dtos;

use App\Modules\Graphql\Domain\Enums\GraphqlEnum;

/**
 * Body de POST /graphql: {query, variables?, operationName?}.
 */
final readonly class GraphqlRequestDto
{
    public function __construct(
        private string $query,
        private array $variables,
        private ?string $operationName,
    ) {
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            self::getScalarAsString($primitives[GraphqlEnum::KEY_QUERY] ?? ""),
            self::getVariablesAsArray($primitives[GraphqlEnum::KEY_VARIABLES] ?? []),
            self::getScalarAsString($primitives[GraphqlEnum::KEY_OPERATION_NAME] ?? "") ?: null,
        );
    }

    /**
     * Un array/objeto donde va un string cuenta como vacio (como HttpRequester::getPostString), no como "Array".
     */
    private static function getScalarAsString(mixed $value): string
    {
        return is_scalar($value) ? trim((string) $value) : "";
    }

    /**
     * "variables" puede llegar como objeto JSON o, en algunos clientes, como string JSON.
     */
    private static function getVariablesAsArray(mixed $variables): array
    {
        if (is_string($variables)) {
            $variables = json_decode($variables, true);
        }
        return is_array($variables) ? $variables : [];
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getVariables(): array
    {
        return $this->variables;
    }

    public function getOperationName(): ?string
    {
        return $this->operationName;
    }
}
