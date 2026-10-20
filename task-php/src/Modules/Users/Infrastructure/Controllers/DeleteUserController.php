<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Users\Application\DeleteUser\DeleteUserDto;
use App\Modules\Users\Application\DeleteUser\DeleteUserService;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * DELETE /api/v1/users/:userUuid
 */
final class DeleteUserController extends AbstractApiController
{
    public function delete(string $userUuid): HttpResponder
    {
        try {
            $deleteUserResultDto = DeleteUserService::getInstance()->__invoke(
                DeleteUserDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "userUuid" => $userUuid,
                ])
            );
            return $this->respondOk($deleteUserResultDto->toArray());
        }
        catch (UsersException $e) {
            return $this->respondDomainError($e);
        }
        catch (Throwable $e) {
            return $this->respondUnexpectedError($e, __METHOD__);
        }
    }
}
