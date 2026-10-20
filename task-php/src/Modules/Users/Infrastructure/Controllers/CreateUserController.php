<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;
use App\Modules\Users\Application\CreateUser\CreateUserDto;
use App\Modules\Users\Application\CreateUser\CreateUserService;
use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * POST /api/v1/users  body: {user_name, user_email, password}  (publico: alta + primer api_token)
 */
final class CreateUserController extends AbstractApiController
{
    public function create(): HttpResponder
    {
        try {
            $createUserResultDto = CreateUserService::getInstance()->__invoke(
                CreateUserDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    "userName" => $this->httpRequest->getPostString(UsersKeyEnum::USER_NAME),
                    "userEmail" => $this->httpRequest->getPostString(UsersKeyEnum::USER_EMAIL),
                    "password" => $this->httpRequest->getPostString(UsersKeyEnum::PASSWORD),
                ])
            );
            return $this->respondOk($createUserResultDto->toArray(), ExceptionEnum::CODE_CREATED);
        }
        catch (FieldsException | UsersException $e) {
            return $this->respondDomainError($e);
        }
        catch (Throwable $e) {
            return $this->respondUnexpectedError($e, __METHOD__);
        }
    }
}
