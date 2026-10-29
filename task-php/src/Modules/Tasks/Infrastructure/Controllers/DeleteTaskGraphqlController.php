<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use GraphQL\Error\Error;

use App\Modules\Shared\Infrastructure\Controllers\AbstractGraphqlController;

use App\Modules\Users\Domain\Exceptions\UsersException;

use App\Modules\Tasks\Domain\Enums\TasksKeyEnum;
use App\Modules\Tasks\Application\DeleteTask\DeleteTaskDto;
use App\Modules\Tasks\Application\DeleteTask\DeleteTaskService;
use App\Modules\Tasks\Domain\Exceptions\TasksException;

/**
 * Mutation deleteTask(task_uuid) (src/Core/Graphql/schema.graphql). El 401 de la autenticacion llega como UsersException.
 */
final class DeleteTaskGraphqlController extends AbstractGraphqlController
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
            return DeleteTaskService::getInstance()->__invoke(
                DeleteTaskDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "taskUuid" => $args[TasksKeyEnum::TASK_UUID] ?? "",
                ])
            )->toArray();
        }
        catch (UsersException | TasksException $e) {
            throw $this->getGraphqlDomainError($e);
        }
        catch (Throwable $e) {
            throw $this->getGraphqlUnexpectedError($e, __METHOD__);
        }
    }
}
