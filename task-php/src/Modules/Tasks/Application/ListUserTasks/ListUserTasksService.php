<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\ListUserTasks;

use App\Modules\Tasks\Domain\Enums\TasksMessageEnum;
use App\Modules\Tasks\Domain\Entities\TaskEntity;
use App\Modules\Tasks\Infrastructure\Repositories\TasksReaderSqliteRepository;
use App\Modules\Tasks\Domain\Exceptions\TasksException;

/**
 * Tasks de un usuario. Usuario inexistente -> 404 (no una lista vacia).
 */
final class ListUserTasksService
{
    private TasksReaderSqliteRepository $tasksReaderSqliteRepository;

    public function __construct()
    {
        $this->tasksReaderSqliteRepository = TasksReaderSqliteRepository::getInstance();
    }

    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws TasksException
     */
    public function __invoke(ListUserTasksDto $listUserTasksDto): ListUserTasksResultDto
    {
        $userUuid = $listUserTasksDto->getUserUuid();
        // solo el propio usuario: otro uuid responde igual que si no existiera (no se revela quien existe)
        if ($userUuid !== $listUserTasksDto->getAuthUserUuid()) {
            TasksException::notFoundCustom(__(TasksMessageEnum::USER_X_NOT_FOUND, $userUuid));
        }

        return ListUserTasksResultDto::fromPrimitives([
            "tasks" => array_map(
                fn(TaskEntity $taskEntity): array => $taskEntity->toPrimitives(),
                $this->tasksReaderSqliteRepository->getTasksByUserId($listUserTasksDto->getAuthUserId())
            ),
        ]);
    }
}
