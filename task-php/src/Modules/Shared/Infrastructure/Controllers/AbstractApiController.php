<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;
use App\Modules\Shared\Infrastructure\Traits\LogTrait;

/**
 * Base de los controllers de la API. Envelope UNICO de respuesta:
 *   {"code": int, "status": bool, "errors": string[], "data": object|array}
 */
abstract class AbstractApiController
{
    use LogTrait;

    /**
     * Body JSON de la peticion (IndexMain lo deja decodificado en $_POST).
     */
    protected function getRequestBody(): array
    {
        return $_POST;
    }

    /**
     * Campo escalar del body como string. Un array/objeto en un campo escalar se trata como vacio
     * (y lo rechaza la validacion de campos obligatorios) en vez de dar "Array" o un TypeError.
     */
    protected function getBodyString(string $key): string
    {
        $value = $this->getRequestBody()[$key] ?? "";
        return is_scalar($value) ? trim((string) $value) : "";
    }

    protected function getQueryParam(string $key): string
    {
        return trim((string) ($_GET[$key] ?? ""));
    }

    protected function respondOk(array $data, int $code = ExceptionEnum::CODE_OK): HttpResponse
    {
        return $this->getJsonResponse($code, true, [], $data);
    }

    protected function respondErrors(int $code, array $errors): HttpResponse
    {
        return $this->getJsonResponse($code, false, $errors, []);
    }

    /**
     * Excepciones con codigo HTTP 4xx (las de dominio y FieldsException) se devuelven tal cual;
     * cualquier otra es un 500 que se loguea y no filtra su mensaje.
     */
    protected function respondThrowable(Throwable $ex, array $errors = [], string $logTitle = ""): HttpResponse
    {
        $code = $ex->getCode();
        if (is_int($code) && $code >= ExceptionEnum::CODE_BAD_REQUEST && $code < ExceptionEnum::CODE_INTERNAL_SERVER_ERROR) {
            return $this->respondErrors($code, $errors ?: [$ex->getMessage()]);
        }

        $this->logErr($ex->getMessage()."\n".$ex->getTraceAsString(), $logTitle ?: static::class);
        return $this->respondErrors(
            ExceptionEnum::CODE_INTERNAL_SERVER_ERROR,
            [__(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED)]
        );
    }

    private function getJsonResponse(int $code, bool $status, array $errors, array $data): HttpResponse
    {
        return HttpResponse::json(
            (string) json_encode([
                "code" => $code,
                "status" => $status,
                "errors" => $errors,
                "data" => $data,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $code
        );
    }
}
