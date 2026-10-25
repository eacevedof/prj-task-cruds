<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\GetTask;

use App\Modules\Tasks\Domain\Entities\TaskEntity;
use App\Modules\Tasks\Domain\Enums\TasksMessageEnum;
use App\Modules\Tasks\Infrastructure\Repositories\TasksReaderSqliteRepository;
use App\Modules\Tasks\Domain\Exceptions\TasksException;

final class GetTaskService
{
    private readonly TasksReaderSqliteRepository $tasksReaderSqliteRepository;

    private GetTaskDto $getTaskDto;

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
        $this->getTaskDto = $getTaskDto;
        $this->failIfWrongInput();

        return GetTaskResultDto::fromPrimitives(["task" => $this->tryToGetOwnTaskOrFail()->toPrimitives()]);
    }

    /**
     * @throws TasksException
     */
    private function failIfWrongInput(): void
    {
        if ($this->getTaskDto->getAuthUserId() < 1) {
            TasksException::unauthorizedCustom(__(TasksMessageEnum::MISSING_OR_INVALID_API_TOKEN));
        }
    }

    /**
     * Una task de otro usuario responde igual que si no existiera (no se revela que existe).
     * @throws TasksException
     */
    private function tryToGetOwnTaskOrFail(): TaskEntity
    {
        $taskUuid = $this->getTaskDto->getTaskUuid();
        $taskEntity = $this->tasksReaderSqliteRepository->getTaskByTaskUuid($taskUuid);
        if (!$taskEntity || $taskEntity->getUserId() !== $this->getTaskDto->getAuthUserId()) {
            TasksException::notFoundCustom(__(TasksMessageEnum::TASK_X_NOT_FOUND, $taskUuid));
        }
        return $taskEntity;
    }
}
