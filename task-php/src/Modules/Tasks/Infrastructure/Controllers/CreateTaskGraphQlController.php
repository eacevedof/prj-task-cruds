<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use App\Modules\Shared\Infrastructure\Controllers\AbstractHttpController;
use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;

use App\Modules\GraphQl\Domain\Exceptions\GraphQlException;

use App\Modules\Users\Domain\Exceptions\UsersException;

use App\Modules\Tasks\Domain\Enums\TasksKeyEnum;
use App\Modules\Tasks\Application\CreateTask\CreateTaskDto;
use App\Modules\Tasks\Application\CreateTask\CreateTaskService;
use App\Modules\Tasks\Domain\Exceptions\TasksException;

final class CreateTaskGraphQlController extends AbstractHttpController
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws GraphQlException
     */
    public function invoke(array $gqlPayload): array
    {
        try {
            return CreateTaskService::getInstance()->__invoke(
                CreateTaskDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "taskTitle" => $gqlPayload[TasksKeyEnum::TASK_TITLE] ?? "",
                    "taskStatus" => $gqlPayload[TasksKeyEnum::TASK_STATUS] ?? "",
                    "taskType" => $gqlPayload[TasksKeyEnum::TASK_TYPE] ?? "",
                    "targetDate" => $gqlPayload[TasksKeyEnum::TARGET_DATE] ?? "",
                    "taskDescription" => $gqlPayload[TasksKeyEnum::TASK_DESCRIPTION] ?? "",
                ])
            )->toArray();
        }
        catch (FieldsException | UsersException | TasksException $e) {
            GraphQlException::clientAwareExceptionForWebonyx($e);
        }
        catch (Throwable $e) {
            $this->logException($e, __METHOD__);
            $this->sendMailOnThrowable($e, __METHOD__);
            GraphQlException::internalServerError(
                __(SharedMessageEnum::AN_UNEXPECTED_ERROR_HAS_OCCURRED)
            );
        }

    }
}
