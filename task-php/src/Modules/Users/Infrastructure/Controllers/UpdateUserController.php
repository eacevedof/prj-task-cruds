<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;
use App\Modules\Users\Application\UpdateUser\UpdateUserDto;
use App\Modules\Users\Application\UpdateUser\UpdateUserService;
use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * PUT /api/v1/users/:userUuid  body: {user_name, user_email}
 */
final class UpdateUserController extends AbstractApiController
{
    public function update(string $userUuid): HttpResponder
    {
        try {
            $updateUserResultDto = UpdateUserService::getInstance()->__invoke(
                UpdateUserDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "userUuid" => $userUuid,
                    "userName" => $this->httpRequest->getPostString(UsersKeyEnum::USER_NAME),
                    "userEmail" => $this->httpRequest->getPostString(UsersKeyEnum::USER_EMAIL),
                ])
            );
            return $this->respondOk($updateUserResultDto->toArray());
        }
        catch (FieldsException | UsersException $e) {
            return $this->respondDomainError($e);
        }
        catch (Throwable $e) {
            return $this->respondUnexpectedError($e, __METHOD__);
        }
    }
}
