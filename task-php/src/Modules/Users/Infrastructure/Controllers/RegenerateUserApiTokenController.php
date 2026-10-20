<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Users\Application\RegenerateUserApiToken\RegenerateUserApiTokenDto;
use App\Modules\Users\Application\RegenerateUserApiToken\RegenerateUserApiTokenService;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * POST /api/v1/users/:userUuid/api-token  (rota el token: el anterior deja de valer)
 */
final class RegenerateUserApiTokenController extends AbstractApiController
{
    public function regenerate(string $userUuid): HttpResponder
    {
        try {
            $regenerateUserApiTokenResultDto = RegenerateUserApiTokenService::getInstance()->__invoke(
                RegenerateUserApiTokenDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "userUuid" => $userUuid,
                ])
            );
            return $this->respondOk($regenerateUserApiTokenResultDto->toArray());
        }
        catch (UsersException $e) {
            return $this->respondDomainError($e);
        }
        catch (Throwable $e) {
            return $this->respondUnexpectedError($e, __METHOD__);
        }
    }
}
