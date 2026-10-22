<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use App\Modules\Users\Application\DeleteUser\DeleteUserDto;
use App\Modules\Users\Application\DeleteUser\DeleteUserService;

/**
 * DELETE /api/v1/users/:userUuid
 */
final class DeleteUserController extends AbstractApiController
{
    public function delete(string $userUuid): HttpResponse
    {
        $deleteUserService = null;
        try {
            $deleteUserService = DeleteUserService::getInstance();
            $deleteUserResultDto = $deleteUserService(DeleteUserDto::fromPrimitives([
                "userUuid" => $userUuid,
            ]));
            return $this->respondOk($deleteUserResultDto->toArray());
        }
        catch (FieldsException $e) {
            return $this->respondThrowable($e, $deleteUserService?->getErrors() ?? []);
        }
        catch (Throwable $e) {
            return $this->respondThrowable($e);
        }
    }
}
