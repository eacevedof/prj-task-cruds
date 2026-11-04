<?php
declare(strict_types=1);

namespace App\Modules\GraphQl\Domain\Exceptions;

use Exception;
use GraphQL\Error\ClientAware;
use GraphQL\Error\ProvidesExtensions;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\GraphQl\Domain\Enums\GraphQlEnum;

/**
 * Lo que lanza un resolver para que su mensaje llegue al cliente: webonyx la envuelve en un GraphQL\Error y, como es
 * ClientAware, no la cambia por "Internal server error". ProvidesExtensions pone su codigo en extensions.code y su
 * mensaje en extensions.errors.
 */
final class GraphQlException extends Exception implements ClientAware, ProvidesExtensions
{
    public function isClientSafe(): bool
    {
        return true;
    }

    public function getExtensions(): array
    {
        return [
            GraphQlEnum::EXTENSION_CODE => $this->getCode(),
            GraphQlEnum::EXTENSION_ERRORS => [$this->getMessage()],
        ];
    }

    /**
     * Excepcion controlada (de dominio o FieldsException): su codigo 4xx y su mensaje pasan tal cual al cliente.
     */
    public static function clientAwareExceptionForWebonyx(Exception $ex): never
    {
        throw new self($ex->getMessage(), $ex->getCode());
    }

    /**
     * Fallo no controlado: 500 con un mensaje generico, el original no sale (el controller ya lo ha logueado).
     */
    public static function internalServerError(string $message): never
    {
        throw new self($message, ExceptionEnum::CODE_INTERNAL_SERVER_ERROR);
    }
}
