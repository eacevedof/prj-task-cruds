<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use App\Modules\Tasks\Application\ListTasks\ListTasksDto;
use App\Modules\Tasks\Application\ListTasks\ListTasksService;

/**
 * GET /api/v1/tasks
 */
final class ListTasksController extends AbstractApiController
{
    public function index(): HttpResponse
    {
        $listTasksService = null;
        try {
            $listTasksService = ListTasksService::getInstance();
            $listTasksResultDto = $listTasksService(ListTasksDto::fromPrimitives([]));
            return $this->respondOk($listTasksResultDto->toArray());
        }
        catch (FieldsException $e) {
            return $this->respondThrowable($e, $listTasksService?->getErrors() ?? []);
        }
        catch (Throwable $e) {
            return $this->respondThrowable($e);
        }
    }
}
