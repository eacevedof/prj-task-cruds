<?php
declare(strict_types=1);

namespace App\Modules\Graphql\Infrastructure\Dtos;

use App\Modules\Graphql\Domain\Enums\GraphqlEnum;

/**
 * Respuesta GraphQL {errors?, data?}: las claves solo van si traen algo y en el orden de webonyx (errors primero).
 */
final readonly class GraphqlResultDto
{
    public function __construct(
        private array $errors,
        private ?array $data,
    ) {
    }

    /**
     * @param array $graphqlResult el ExecutionResult::toArray() de webonyx
     */
    public static function fromPrimitives(array $graphqlResult): self
    {
        return new self(
            $graphqlResult[GraphqlEnum::KEY_ERRORS] ?? [],
            $graphqlResult[GraphqlEnum::KEY_DATA] ?? null,
        );
    }

    /**
     * Error antes de ejecutar (peticion sin query, fallo inesperado): sin data, como lo daria GraphQL.
     */
    public static function fromErrorMessage(string $message): self
    {
        return new self([[GraphqlEnum::KEY_MESSAGE => $message]], null);
    }

    public function toArray(): array
    {
        $graphqlResult = [];
        if ($this->errors) $graphqlResult[GraphqlEnum::KEY_ERRORS] = $this->errors;
        if ($this->data !== null) $graphqlResult[GraphqlEnum::KEY_DATA] = $this->data;
        return $graphqlResult;
    }
}
