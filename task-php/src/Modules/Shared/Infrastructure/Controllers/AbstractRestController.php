<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Controllers;

use Exception;
use Throwable;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Domain\Enums\HttpEnum;
use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;

/**
 * Base de los controllers REST. Envelope UNICO de respuesta, con el codigo tambien como status HTTP:
 *   {"code": int, "status": bool, "errors": string[], "data": object|array}
 */
abstract class AbstractRestController extends AbstractHttpController
{
    protected function respondOk(array $data, int $code = ExceptionEnum::CODE_OK): HttpResponder
    {
        return $this->getJsonResponse($code, true, [], $data);
    }

    protected function respondErrors(int $code, array $errors): HttpResponder
    {
        return $this->getJsonResponse($code, false, $errors, []);
    }

    /**
     * Excepcion controlada (de dominio o FieldsException): su codigo 4xx y su mensaje viajan al cliente tal cual
     * (una FieldsException ya trae en el mensaje el detalle por campo). El controller la captura por su nombre.
     */
    protected function respondDomainError(Exception $ex): HttpResponder
    {
        return $this->respondErrors($ex->getCode(), [$ex->getMessage()]);
    }

    /**
     * Cualquier otra cosa: 500, se loguea con el metodo que fallo (__METHOD__) y no filtra su mensaje.
     */
    protected function respondUnexpectedError(Throwable $ex, string $logTitle): HttpResponder
    {
        $this->logException($ex, $logTitle);
        $this->sendMailOnThrowable($ex, $logTitle);
        return $this->respondErrors(
            ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
            [__(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED)]
        );
    }

    private function getJsonResponse(int $code, bool $status, array $errors, array $data): HttpResponder
    {
        return HttpResponder::json(
            (string) json_encode([
                HttpEnum::KEY_CODE => $code,
                HttpEnum::KEY_STATUS => $status,
                HttpEnum::KEY_ERRORS => $errors,
                HttpEnum::KEY_DATA => $data,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $code,
            // RFC 6750: un 401 indica al cliente el esquema que se espera
            $code === ExceptionEnum::CODE_UNAUTHORIZED
                ? [HttpEnum::HEADER_WWW_AUTHENTICATE => HttpEnum::AUTH_SCHEME_BEARER]
                : []
        );
    }
}
