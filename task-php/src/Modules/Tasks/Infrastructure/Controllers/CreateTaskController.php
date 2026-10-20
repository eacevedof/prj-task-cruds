<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Domain\Enums\ExceptionEnum;
use App\Modules\Shared\Infrastructure\Components\HttpResponder\HttpResponder;
use App\Modules\Shared\Infrastructure\Controllers\AbstractApiController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;
use App\Modules\Tasks\Application\CreateTask\CreateTaskDto;
use App\Modules\Tasks\Application\CreateTask\CreateTaskService;
use App\Modules\Tasks\Domain\Enums\TasksKeyEnum;
use App\Modules\Users\Domain\Exceptions\UsersException;

/**
 * POST /api/v1/tasks  body: {task_title, task_status?, task_type?, target_date?, task_description?}
 */
final class CreateTaskController extends AbstractApiController
{
    public function create(): HttpResponder
    {
        try {
            $createTaskResultDto = CreateTaskService::getInstance()->__invoke(
                CreateTaskDto::fromPrimitives([
                    ...$this->getAuthUserOrFail(),
                    ...$this->httpRequest->getMetadata(),
                    "taskTitle" => $this->httpRequest->getPostString(TasksKeyEnum::TASK_TITLE),
                    "taskStatus" => $this->httpRequest->getPostString(TasksKeyEnum::TASK_STATUS),
                    "taskType" => $this->httpRequest->getPostString(TasksKeyEnum::TASK_TYPE),
                    "targetDate" => $this->httpRequest->getPostString(TasksKeyEnum::TARGET_DATE),
                    "taskDescription" => $this->httpRequest->getPostString(TasksKeyEnum::TASK_DESCRIPTION),
                ])
            );
            return $this->respondOk($createTaskResultDto->toArray(), ExceptionEnum::CODE_CREATED);
        }
        catch (FieldsException | UsersException $e) {
            return $this->respondDomainError($e);
        }
        catch (Throwable $e) {
            return $this->respondUnexpectedError($e, __METHOD__);
        }
    }
}
