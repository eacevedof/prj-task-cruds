<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\DeleteTask;

use App\Core\Application\Services\AbstractAppService;

use App\Modules\Tasks\Domain\Enums\TasksMessageEnum;
use App\Modules\Tasks\Domain\Exceptions\TasksException;
use App\Modules\Tasks\Infrastructure\Repositories\TasksReaderSqliteRepository;
use App\Modules\Tasks\Infrastructure\Repositories\TasksWriterSqliteRepository;

final class DeleteTaskService extends AbstractAppService
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
        if (!$this->tasksReaderSqliteRepository->getTaskByTaskUuid($taskUuid)) {
            TasksException::notFoundCustom(__(TasksMessageEnum::TASK_X_NOT_FOUND, $taskUuid));
        }

        return DeleteTaskResultDto::fromPrimitives([
            "taskUuid" => $taskUuid,
            "affected" => $this->tasksWriterSqliteRepository->deleteTaskByTaskUuid($taskUuid),
        ]);
    }
}
