<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Users\Application\GetUser\GetUserDto;
use App\Modules\Users\Application\GetUser\GetUserService;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * GET /api/v1/users/:userUuid
 */
final class GetUserController extends AbstractApiController
{
    public function show(string $userUuid): HttpResponder
    {
        try {
            $getUserResultDto = GetUserService::getInstance()->__invoke(
                GetUserDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "userUuid" => $userUuid,
                ])
            );
            return $this->respondOk($getUserResultDto->toArray());
        }
        catch (UsersException $e) {
            return $this->respondDomainError($e);
        }
        catch (Throwable $e) {
            return $this->respondUnexpectedError($e, __METHOD__);
        }
    }
}
