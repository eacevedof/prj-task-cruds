<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use App\Modules\Users\Application\GetUser\GetUserDto;
use App\Modules\Users\Application\GetUser\GetUserService;

/**
 * GET /api/v1/users/:userUuid
 */
final class GetUserController extends AbstractApiController
{
    public function show(string $userUuid): HttpResponse
    {
        $getUserService = null;
        try {
            $getUserService = GetUserService::getInstance();
            $getUserResultDto = $getUserService(GetUserDto::fromPrimitives([
                "userUuid" => $userUuid,
            ]));
            return $this->respondOk($getUserResultDto->toArray());
        }
        catch (FieldsException $e) {
            return $this->respondThrowable($e, $getUserService?->getErrors() ?? []);
        }
        catch (Throwable $e) {
            return $this->respondThrowable($e);
        }
    }
}
