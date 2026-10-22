<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use App\Modules\Tasks\Application\GetTask\GetTaskDto;
use App\Modules\Tasks\Application\GetTask\GetTaskService;

/**
 * GET /api/v1/tasks/:taskUuid
 */
final class GetTaskController extends AbstractApiController
{
    public function show(string $taskUuid): HttpResponse
    {
        $getTaskService = null;
        try {
            $getTaskService = GetTaskService::getInstance();
            $getTaskResultDto = $getTaskService(GetTaskDto::fromPrimitives([
                "taskUuid" => $taskUuid,
            ]));
            return $this->respondOk($getTaskResultDto->toArray());
        }
        catch (FieldsException $e) {
            return $this->respondThrowable($e, $getTaskService?->getErrors() ?? []);
        }
        catch (Throwable $e) {
            return $this->respondThrowable($e);
        }
    }
}
