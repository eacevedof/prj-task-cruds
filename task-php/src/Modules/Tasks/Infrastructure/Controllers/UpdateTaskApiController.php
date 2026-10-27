<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use GraphQL\Error\Error;

use App\Modules\Shared\Infrastructure\Controllers\AbstractGraphqlController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;

use App\Modules\Users\Domain\Exceptions\UsersException;

use App\Modules\Tasks\Domain\Enums\TasksKeyEnum;
use App\Modules\Tasks\Application\UpdateTask\UpdateTaskDto;
use App\Modules\Tasks\Application\UpdateTask\UpdateTaskService;
use App\Modules\Tasks\Domain\Exceptions\TasksException;

/**
 * Mutation updateTask (src/Core/Graphql/schema.graphql). El 401 de la autenticacion llega como UsersException.
 */
final class UpdateTaskApiController extends AbstractGraphqlController
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws Error
     */
    public function invoke(array $args): array
    {
        try {
            return UpdateTaskService::getInstance()->__invoke(
                UpdateTaskDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "taskUuid" => $args[TasksKeyEnum::TASK_UUID] ?? "",
                    "taskTitle" => $args[TasksKeyEnum::TASK_TITLE] ?? "",
                    "taskStatus" => $args[TasksKeyEnum::TASK_STATUS] ?? "",
                    "taskType" => $args[TasksKeyEnum::TASK_TYPE] ?? "",
                    "targetDate" => $args[TasksKeyEnum::TARGET_DATE] ?? "",
                    "taskDescription" => $args[TasksKeyEnum::TASK_DESCRIPTION] ?? "",
                ])
            )->toArray();
        }
        catch (FieldsException | UsersException | TasksException $e) {
            throw $this->getGraphqlDomainError($e);
        }
        catch (Throwable $e) {
            throw $this->getGraphqlUnexpectedError($e, __METHOD__);
        }
    }
}
