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
use App\Modules\Tasks\Application\SearchTasks\SearchTasksDto;
use App\Modules\Tasks\Application\SearchTasks\SearchTasksService;
use App\Modules\Tasks\Domain\Exceptions\TasksException;

/**
 * Query tasks (src/Modules/Tasks/Infrastructure/Providers/tasks.graphql): las tasks del usuario autenticado, paginadas y
 * con filtros. User.tasks (las de un usuario como campo de User) va por SearchTasksByUserGraphQlController.
 * El 401 de la autenticacion llega como UsersException.
 */
final class SearchTasksGraphQlController extends AbstractHttpController
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @param array $gqlPayload {filter?: {task_text?, task_status?, task_type?, target_date_from?, target_date_to?}, page?, page_size?}
     * @throws GraphQlException
     */
    public function invoke(array $gqlPayload): array
    {
        try {
            $filter = $gqlPayload[TasksKeyEnum::FILTER] ?? [];
            return SearchTasksService::getInstance()->__invoke(
                SearchTasksDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "taskText" => $filter[TasksKeyEnum::TASK_TEXT] ?? "",
                    "taskStatus" => $filter[TasksKeyEnum::TASK_STATUS] ?? "",
                    "taskType" => $filter[TasksKeyEnum::TASK_TYPE] ?? "",
                    "targetDateFrom" => $filter[TasksKeyEnum::TARGET_DATE_FROM] ?? "",
                    "targetDateTo" => $filter[TasksKeyEnum::TARGET_DATE_TO] ?? "",
                    "page" => $gqlPayload[TasksKeyEnum::PAGE] ?? null,
                    "pageSize" => $gqlPayload[TasksKeyEnum::PAGE_SIZE] ?? null,
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
