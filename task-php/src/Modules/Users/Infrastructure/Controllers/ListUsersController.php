<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Users\Application\ListUsers\ListUsersDto;
use App\Modules\Users\Application\ListUsers\ListUsersService;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * GET /api/v1/users  (solo el usuario del token)
 */
final class ListUsersController extends AbstractApiController
{
    public function index(): HttpResponder
    {
        try {
            $listUsersResultDto = ListUsersService::getInstance()->__invoke(
                ListUsersDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                ])
            );
            return $this->respondOk($listUsersResultDto->toArray());
        }
        catch (UsersException $e) {
            return $this->respondDomainError($e);
        }
        catch (Throwable $e) {
            return $this->respondUnexpectedError($e, __METHOD__);
        }
    }
}
