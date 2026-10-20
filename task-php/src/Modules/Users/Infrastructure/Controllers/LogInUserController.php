<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Users\Application\LogInUser\LogInUserDto;
use App\Modules\Users\Application\LogInUser\LogInUserService;
use App\Modules\Users\Domain\Enums\UsersKeyEnum;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * POST /api/v1/log-in  body: {user_name, password}  (publico: devuelve el usuario y un api_token nuevo)
 */
final class LogInUserController extends AbstractApiController
{
    public function logIn(): HttpResponder
    {
        try {
            $logInUserResultDto = LogInUserService::getInstance()->__invoke(
                LogInUserDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    "userName" => $this->httpRequest->getPostString(UsersKeyEnum::USER_NAME),
                    "password" => $this->httpRequest->getPostString(UsersKeyEnum::PASSWORD),
                ])
            );
            return $this->respondOk($logInUserResultDto->toArray());
        }
        catch (UsersException $e) {
            return $this->respondDomainError($e);
        }
        catch (Throwable $e) {
            return $this->respondUnexpectedError($e, __METHOD__);
        }
    }
}
