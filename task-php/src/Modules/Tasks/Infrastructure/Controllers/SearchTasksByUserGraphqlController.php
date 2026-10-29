<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use GraphQL\Error\Error;

use App\Modules\Graphql\Infrastructure\Controllers\AbstractGraphqlController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;

use App\Modules\Users\Domain\Exceptions\UsersException;

use App\Modules\Tasks\Domain\Enums\TasksKeyEnum;
use App\Modules\Tasks\Application\SearchTasks\SearchTasksDto;
use App\Modules\Tasks\Application\SearchTasks\SearchTasksService;
use App\Modules\Tasks\Domain\Exceptions\TasksException;

/**
 * Query userTasks y campo User.tasks (src/Modules/Tasks/Infrastructure/Providers/tasks.graphql): las tasks de un
 * usuario, paginadas y con filtros. El uuid llega aparte porque en User.tasks sale del User padre, no de los args.
 * Un usuario que no es el autenticado responde 404. El 401 de la autenticacion llega como UsersException.
 */
final class SearchTasksByUserGraphqlController extends AbstractGraphqlController
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @param array $args {filter?: {task_text?, task_status?, task_type?, target_date_from?, target_date_to?}, page?, page_size?}
     * @throws Error
     */
    public function invoke(string $userUuid, array $args): array
    {
        try {
            $filter = $args[TasksKeyEnum::FILTER] ?? [];
            return SearchTasksService::getInstance()->__invoke(
                SearchTasksDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "userUuid" => $userUuid,
                    "taskText" => $filter[TasksKeyEnum::TASK_TEXT] ?? "",
                    "taskStatus" => $filter[TasksKeyEnum::TASK_STATUS] ?? "",
                    "taskType" => $filter[TasksKeyEnum::TASK_TYPE] ?? "",
                    "targetDateFrom" => $filter[TasksKeyEnum::TARGET_DATE_FROM] ?? "",
                    "targetDateTo" => $filter[TasksKeyEnum::TARGET_DATE_TO] ?? "",
                    "page" => $args[TasksKeyEnum::PAGE] ?? null,
                    "pageSize" => $args[TasksKeyEnum::PAGE_SIZE] ?? null,
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
