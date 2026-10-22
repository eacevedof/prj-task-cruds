<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use App\Modules\Users\Application\CreateUser\CreateUserDto;
use App\Modules\Users\Application\CreateUser\CreateUserService;

/**
 * POST /api/v1/users  body: {user_name, user_email}
 */
final class CreateUserController extends AbstractApiController
{
    public function create(): HttpResponse
    {
        $createUserService = null;
        try {
            $createUserService = CreateUserService::getInstance();
            $createUserResultDto = $createUserService(CreateUserDto::fromPrimitives([
                "userName" => $this->getBodyString("user_name"),
                "userEmail" => $this->getBodyString("user_email"),
            ]));
            return $this->respondOk($createUserResultDto->toArray(), ExceptionEnum::CODE_CREATED);
        }
        catch (FieldsException $e) {
            return $this->respondThrowable($e, $createUserService?->getErrors() ?? []);
        }
        catch (Throwable $e) {
            return $this->respondThrowable($e);
        }
    }
}
