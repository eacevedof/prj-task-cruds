<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use App\Modules\Users\Application\UpdateUser\UpdateUserDto;
use App\Modules\Users\Application\UpdateUser\UpdateUserService;

/**
 * PUT /api/v1/users/:userUuid  body: {user_name, user_email}
 */
final class UpdateUserController extends AbstractApiController
{
    public function update(string $userUuid): HttpResponse
    {
        $updateUserService = null;
        try {
            $updateUserService = UpdateUserService::getInstance();
            $updateUserResultDto = $updateUserService(UpdateUserDto::fromPrimitives([
                "userUuid" => $userUuid,
                "userName" => $this->getBodyString("user_name"),
                "userEmail" => $this->getBodyString("user_email"),
            ]));
            return $this->respondOk($updateUserResultDto->toArray());
        }
        catch (FieldsException $e) {
            return $this->respondThrowable($e, $updateUserService?->getErrors() ?? []);
        }
        catch (Throwable $e) {
            return $this->respondThrowable($e);
        }
    }
}
