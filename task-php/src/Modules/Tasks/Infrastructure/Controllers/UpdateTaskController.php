<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;
use App\Modules\Tasks\Application\UpdateTask\UpdateTaskDto;
use App\Modules\Tasks\Application\UpdateTask\UpdateTaskService;
use App\Modules\Tasks\Domain\Enums\TasksKeyEnum;
use App\Modules\Tasks\Domain\Exceptions\TasksException;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * PUT /api/v1/tasks/:taskUuid  body: {task_title, task_status?, task_type?, target_date?, task_description?}
 */
final class UpdateTaskController extends AbstractApiController
{
    public function update(string $taskUuid): HttpResponder
    {
        try {
            $updateTaskResultDto = UpdateTaskService::getInstance()->__invoke(
                UpdateTaskDto::fromPrimitives([
                    ...$this->getAuthUserOrFail(),
                    ...$this->httpRequest->getMetadata(),
                    "taskUuid" => $taskUuid,
                    "taskTitle" => $this->httpRequest->getPostString(TasksKeyEnum::TASK_TITLE),
                    "taskStatus" => $this->httpRequest->getPostString(TasksKeyEnum::TASK_STATUS),
                    "taskType" => $this->httpRequest->getPostString(TasksKeyEnum::TASK_TYPE),
                    "targetDate" => $this->httpRequest->getPostString(TasksKeyEnum::TARGET_DATE),
                    "taskDescription" => $this->httpRequest->getPostString(TasksKeyEnum::TASK_DESCRIPTION),
                ])
            );
            return $this->respondOk($updateTaskResultDto->toArray());
        }
        catch (FieldsException | UsersException | TasksException $e) {
            return $this->respondDomainError($e);
        }
        catch (Throwable $e) {
            return $this->respondUnexpectedError($e, __METHOD__);
        }
    }
}
