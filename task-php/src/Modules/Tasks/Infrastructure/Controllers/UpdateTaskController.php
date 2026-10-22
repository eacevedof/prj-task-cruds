<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use App\Modules\Tasks\Application\UpdateTask\UpdateTaskDto;
use App\Modules\Tasks\Application\UpdateTask\UpdateTaskService;

/**
 * PUT /api/v1/tasks/:taskUuid  body: {task_title, task_status?, task_type?, target_date?, task_description?}
 */
final class UpdateTaskController extends AbstractApiController
{
    public function update(string $taskUuid): HttpResponse
    {
        $updateTaskService = null;
        try {
            $updateTaskService = UpdateTaskService::getInstance();
            $updateTaskResultDto = $updateTaskService(UpdateTaskDto::fromPrimitives([
                "taskUuid" => $taskUuid,
                "taskTitle" => $this->getBodyString("task_title"),
                "taskStatus" => $this->getBodyString("task_status"),
                "taskType" => $this->getBodyString("task_type"),
                "targetDate" => $this->getBodyString("target_date"),
                "taskDescription" => $this->getBodyString("task_description"),
            ]));
            return $this->respondOk($updateTaskResultDto->toArray());
        }
        catch (FieldsException $e) {
            return $this->respondThrowable($e, $updateTaskService?->getErrors() ?? []);
        }
        catch (Throwable $e) {
            return $this->respondThrowable($e);
        }
    }
}
