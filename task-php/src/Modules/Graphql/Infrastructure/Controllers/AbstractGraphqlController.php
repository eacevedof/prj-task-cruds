<?php
declare(strict_types=1);

namespace App\Modules\Graphql\Infrastructure\Controllers;

use Exception;
use Throwable;

use GraphQL\Error\Error;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Controllers\AbstractHttpController;

use App\Modules\Graphql\Domain\Enums\GraphqlEnum;

/**
 * Base de los resolvers GraphQL (XxxGraphqlController, uno por caso de uso de Users y Tasks): son controllers (construyen el DTO, llaman
 * al servicio y capturan), pero en vez de un HttpResponder devuelven el array del ResultDto o lanzan un Error de
 * GraphQL. Los Error se crean sin $previous: con el graphql-php ocultaria el mensaje al cliente.
 */
abstract class AbstractGraphqlController extends AbstractHttpController
{
    /**
     * Excepcion controlada (de dominio o FieldsException), capturada por su nombre: su codigo 4xx va en
     * extensions.code y su mensaje en extensions.errors (una FieldsException ya trae en el mensaje el detalle por campo).
     */
    protected function getGraphqlDomainError(Exception $ex): Error
    {
        return new Error($ex->getMessage(), extensions: [
            GraphqlEnum::EXTENSION_CODE => $ex->getCode(),
            GraphqlEnum::EXTENSION_ERRORS => [$ex->getMessage()],
        ]);
    }

    /**
     * Cualquier otra cosa: 500, se loguea con el resolver que fallo (__METHOD__) y no filtra su mensaje.
     */
    protected function getGraphqlUnexpectedError(Throwable $ex, string $logTitle): Error
    {
        $this->logException($ex, $logTitle);
        $this->sendMailOnThrowable($ex, $logTitle);
        $unexpectedError = __(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED);
        return new Error($unexpectedError, extensions: [
            GraphqlEnum::EXTENSION_CODE => ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
            GraphqlEnum::EXTENSION_ERRORS => [$unexpectedError],
        ]);
    }
}
