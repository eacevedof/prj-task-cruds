<?php
declare(strict_types=1);

namespace App\Modules\Users\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use App\Modules\Users\Application\ListUsers\ListUsersDto;
use App\Modules\Users\Application\ListUsers\ListUsersService;

/**
 * GET /api/v1/users
 */
final class ListUsersController extends AbstractApiController
{
    public function index(): HttpResponse
    {
        $listUsersService = null;
        try {
            $listUsersService = ListUsersService::getInstance();
            $listUsersResultDto = $listUsersService(ListUsersDto::fromPrimitives([]));
            return $this->respondOk($listUsersResultDto->toArray());
        }
        catch (FieldsException $e) {
            return $this->respondThrowable($e, $listUsersService?->getErrors() ?? []);
        }
        catch (Throwable $e) {
            return $this->respondThrowable($e);
        }
    }
}
