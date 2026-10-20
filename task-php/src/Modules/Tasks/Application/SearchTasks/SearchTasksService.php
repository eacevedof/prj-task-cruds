<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\SearchTasks;

use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Traits\ErrorStackTrait;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;

use App\Modules\Tasks\Domain\Enums\TasksMessageEnum;
use App\Modules\Tasks\Domain\Entities\TaskEntity;
use App\Modules\Tasks\Domain\Services\TasksSearchValidator;
use App\Modules\Tasks\Infrastructure\Repositories\TasksReaderSqliteRepository;
use App\Modules\Tasks\Domain\Exceptions\TasksException;

/**
 * Busqueda paginada de las tasks del usuario autenticado (mas reciente primero): filtros por texto, estado,
 * tipo y rango de target_date. Es la de GraphQL; REST sigue listando todas (ListTasks / ListUserTasks).
 */
final class SearchTasksService
{
    use ErrorStackTrait;

    private TasksSearchValidator $tasksSearchValidator;
    private TasksReaderSqliteRepository $tasksReaderSqliteRepository;
    private SearchTasksDto $searchTasksDto;

    public function __construct()
    {
        $this->tasksSearchValidator = TasksSearchValidator::getInstance();
        $this->tasksReaderSqliteRepository = TasksReaderSqliteRepository::getInstance();
    }

    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws FieldsException
     * @throws TasksException
     */
    public function __invoke(SearchTasksDto $searchTasksDto): SearchTasksResultDto
    {
        $this->searchTasksDto = $searchTasksDto;

        $userId = $this->tryToGetOwnUserIdOrFail();
        $this->failIfWrongSearch();

        $totalItems = $this->tasksReaderSqliteRepository->getTasksCountByUserIdAndFilters(
            $userId,
            $this->searchTasksDto->getTaskText(),
            $this->searchTasksDto->getTaskStatus(),
            $this->searchTasksDto->getTaskType(),
            $this->searchTasksDto->getTargetDateFrom(),
            $this->searchTasksDto->getTargetDateTo()
        );
        $page = $this->searchTasksDto->getPage();
        $pageSize = $this->searchTasksDto->getPageSize();
        $taskEntities = $this->tasksReaderSqliteRepository->getTasksByUserIdAndFilters(
            $userId,
            $this->searchTasksDto->getTaskText(),
            $this->searchTasksDto->getTaskStatus(),
            $this->searchTasksDto->getTaskType(),
            $this->searchTasksDto->getTargetDateFrom(),
            $this->searchTasksDto->getTargetDateTo(),
            $pageSize,
            ($page - 1) * $pageSize
        );

        return SearchTasksResultDto::fromPrimitives([
            "tasks" => array_map(fn(TaskEntity $taskEntity): array => $taskEntity->toPrimitives(), $taskEntities),
            "page" => $page,
            "pageSize" => $pageSize,
            "totalItems" => $totalItems,
            "totalPages" => (int) ceil($totalItems / $pageSize),
        ]);
    }

    /**
     * Sin user_uuid se busca en las del autenticado; con uno ajeno responde igual que si no existiera.
     * @throws TasksException
     */
    private function tryToGetOwnUserIdOrFail(): int
    {
        $userUuid = $this->searchTasksDto->getUserUuid() ?: $this->searchTasksDto->getAuthUserUuid();
        if ($userUuid !== $this->searchTasksDto->getAuthUserUuid()) {
            TasksException::notFoundCustom(__(TasksMessageEnum::USER_X_NOT_FOUND, $userUuid));
        }
        return $this->searchTasksDto->getAuthUserId();
    }

    /**
     * @throws FieldsException
     */
    private function failIfWrongSearch(): void
    {
        $errors = $this->tasksSearchValidator->getErrors(
            $this->searchTasksDto->getTaskStatus(),
            $this->searchTasksDto->getTaskType(),
            $this->searchTasksDto->getTargetDateFrom(),
            $this->searchTasksDto->getTargetDateTo(),
            $this->searchTasksDto->getPage(),
            $this->searchTasksDto->getPageSize()
        );
        if (!$errors) return;

        FieldsException::badRequestCustom(
            $this->getErrorStackMessage(
                __(SharedMessageEnum::FIELDS_VALIDATION_ERRORS), $errors
            ));
    }
}
