<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\DeleteTask;

use App\Modules\Tasks\Domain\Enums\TasksMessageEnum;
use App\Modules\Tasks\Infrastructure\Repositories\TasksReaderSqliteRepository;
use App\Modules\Tasks\Infrastructure\Repositories\TasksWriterSqliteRepository;
use App\Modules\Tasks\Domain\Exceptions\TasksException;

final class DeleteTaskService
{
    private TasksReaderSqliteRepository $tasksReaderSqliteRepository;
    private TasksWriterSqliteRepository $tasksWriterSqliteRepository;

    public function __construct()
    {
        $this->tasksReaderSqliteRepository = TasksReaderSqliteRepository::getInstance();
        $this->tasksWriterSqliteRepository = TasksWriterSqliteRepository::getInstance();
    }

    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws TasksException
     */
    public function __invoke(DeleteTaskDto $deleteTaskDto): DeleteTaskResultDto
    {
        $taskUuid = $deleteTaskDto->getTaskUuid();
        // una task de otro usuario responde igual que si no existiera (no se revela que existe)
        $taskEntity = $this->tasksReaderSqliteRepository->getTaskByTaskUuid($taskUuid);
        if (!$taskEntity || $taskEntity->getUserId() !== $deleteTaskDto->getAuthUserId()) {
            TasksException::notFoundCustom(__(TasksMessageEnum::TASK_X_NOT_FOUND, $taskUuid));
        }

        return DeleteTaskResultDto::fromPrimitives([
            "taskUuid" => $taskUuid,
            "affected" => $this->tasksWriterSqliteRepository->deleteTaskById($taskEntity->getId()),
        ]);
    }
}
