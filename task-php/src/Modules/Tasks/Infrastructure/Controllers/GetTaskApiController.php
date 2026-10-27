<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use GraphQL\Error\Error;

use App\Modules\Shared\Infrastructure\Controllers\AbstractGraphqlController;

use App\Modules\Users\Domain\Exceptions\UsersException;

use App\Modules\Tasks\Domain\Enums\TasksKeyEnum;
use App\Modules\Tasks\Application\GetTask\GetTaskDto;
use App\Modules\Tasks\Application\GetTask\GetTaskService;
use App\Modules\Tasks\Domain\Exceptions\TasksException;

/**
 * Query task(task_uuid) (src/Core/Graphql/schema.graphql). El 401 de la autenticacion llega como UsersException.
 */
final class GetTaskApiController extends AbstractGraphqlController
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
            return GetTaskService::getInstance()->__invoke(
                GetTaskDto::fromPrimitives([
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
