<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\ListUserTasks;

use App\Core\Application\Services\AbstractAppService;

use App\Modules\Tasks\Domain\Enums\TasksMessageEnum;
use App\Modules\Tasks\Domain\Exceptions\TasksException;
use App\Modules\Tasks\Infrastructure\Repositories\TasksReaderSqliteRepository;

/**
 * Tasks de un usuario. Usuario inexistente -> 404 (no una lista vacia).
 */
final class ListUserTasksService extends AbstractAppService
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
        if (!$userId = $this->tasksReaderSqliteRepository->getUserIdByUserUuid($userUuid)) {
            TasksException::notFoundCustom(__(TasksMessageEnum::USER_X_NOT_FOUND, $userUuid));
        }

        return ListUserTasksResultDto::fromPrimitives([
            "taskEntities" => $this->tasksReaderSqliteRepository->getTasksByUserId($userId),
        ]);
    }
}
