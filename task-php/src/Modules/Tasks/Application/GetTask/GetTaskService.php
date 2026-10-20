<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\GetTask;

use App\Modules\Tasks\Domain\Enums\TasksMessageEnum;
use App\Modules\Tasks\Infrastructure\Repositories\TasksReaderSqliteRepository;
use App\Modules\Tasks\Domain\Exceptions\TasksException;

final class GetTaskService
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
    public function __invoke(GetTaskDto $getTaskDto): GetTaskResultDto
    {
        $taskUuid = $getTaskDto->getTaskUuid();
        // una task de otro usuario responde igual que si no existiera (no se revela que existe)
        $taskEntity = $this->tasksReaderSqliteRepository->getTaskByTaskUuid($taskUuid);
        if (!$taskEntity || $taskEntity->getUserId() !== $getTaskDto->getAuthUserId()) {
            TasksException::notFoundCustom(__(TasksMessageEnum::TASK_X_NOT_FOUND, $taskUuid));
        }

        return GetTaskResultDto::fromPrimitives(["task" => $taskEntity->toPrimitives()]);
    }
}
