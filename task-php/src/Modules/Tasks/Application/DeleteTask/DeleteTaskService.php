<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\DeleteTask;

use App\Modules\Tasks\Domain\Entities\TaskEntity;
use App\Modules\Tasks\Domain\Enums\TasksMessageEnum;
use App\Modules\Tasks\Infrastructure\Repositories\TasksReaderSqliteRepository;
use App\Modules\Tasks\Infrastructure\Repositories\TasksWriterSqliteRepository;
use App\Modules\Tasks\Domain\Exceptions\TasksException;

final class DeleteTaskService
{
    private readonly TasksReaderSqliteRepository $tasksReaderSqliteRepository;
    private readonly TasksWriterSqliteRepository $tasksWriterSqliteRepository;

    private DeleteTaskDto $deleteTaskDto;

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
        $this->deleteTaskDto = $deleteTaskDto;
        $this->failIfWrongInput();

        $taskEntity = $this->tryToGetOwnTaskOrFail();

        return DeleteTaskResultDto::fromPrimitives([
            "taskUuid" => $taskEntity->getTaskUuid(),
            "affected" => $this->tasksWriterSqliteRepository->deleteTaskById($taskEntity),
        ]);
    }

    /**
     * @throws TasksException
     */
    private function failIfWrongInput(): void
    {
        if ($this->deleteTaskDto->getAuthUserId() < 1) {
            TasksException::unauthorizedCustom(__(TasksMessageEnum::MISSING_OR_INVALID_API_TOKEN));
        }
    }

    /**
     * Una task de otro usuario responde igual que si no existiera (no se revela que existe).
     * @throws TasksException
     */
    private function tryToGetOwnTaskOrFail(): TaskEntity
    {
        $taskUuid = $this->deleteTaskDto->getTaskUuid();
        $taskEntity = $this->tasksReaderSqliteRepository->getTaskByTaskUuid($taskUuid);
        if (!$taskEntity || $taskEntity->getUserId() !== $this->deleteTaskDto->getAuthUserId()) {
            TasksException::notFoundCustom(__(TasksMessageEnum::TASK_X_NOT_FOUND, $taskUuid));
        }
        return $taskEntity;
    }
}
