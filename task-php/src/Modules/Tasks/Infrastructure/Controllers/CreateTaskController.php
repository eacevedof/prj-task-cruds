<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;
use App\Modules\Shared\Infrastructure\Http\HttpResponse;

use App\Modules\Tasks\Application\CreateTask\CreateTaskDto;
use App\Modules\Tasks\Application\CreateTask\CreateTaskService;

/**
 * POST /api/v1/users/:userUuid/tasks  body: {task_title, task_status?, task_type?, target_date?, task_description?}
 */
final class CreateTaskController extends AbstractApiController
{
    public function create(string $userUuid): HttpResponse
    {
        $createTaskService = null;
        try {
            $createTaskService = CreateTaskService::getInstance();
            $createTaskResultDto = $createTaskService(CreateTaskDto::fromPrimitives([
                "userUuid" => $userUuid,
                "taskTitle" => $this->getBodyString("task_title"),
                "taskStatus" => $this->getBodyString("task_status"),
                "taskType" => $this->getBodyString("task_type"),
                "targetDate" => $this->getBodyString("target_date"),
                "taskDescription" => $this->getBodyString("task_description"),
            ]));
            return $this->respondOk($createTaskResultDto->toArray(), ExceptionEnum::CODE_CREATED);
        }
        catch (FieldsException $e) {
            return $this->respondThrowable($e, $createTaskService?->getErrors() ?? []);
        }
        catch (Throwable $e) {
            return $this->respondThrowable($e);
        }
    }
}
