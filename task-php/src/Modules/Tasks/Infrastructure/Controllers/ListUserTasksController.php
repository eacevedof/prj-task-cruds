<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use App\Modules\Tasks\Application\ListUserTasks\ListUserTasksDto;
use App\Modules\Tasks\Application\ListUserTasks\ListUserTasksService;

/**
 * GET /api/v1/users/:userUuid/tasks
 */
final class ListUserTasksController extends AbstractApiController
{
    public function index(string $userUuid): HttpResponse
    {
        $listUserTasksService = null;
        try {
            $listUserTasksService = ListUserTasksService::getInstance();
            $listUserTasksResultDto = $listUserTasksService(ListUserTasksDto::fromPrimitives([
                "userUuid" => $userUuid,
            ]));
            return $this->respondOk($listUserTasksResultDto->toArray());
        }
        catch (FieldsException $e) {
            return $this->respondThrowable($e, $listUserTasksService?->getErrors() ?? []);
        }
        catch (Throwable $e) {
            return $this->respondThrowable($e);
        }
    }
}
