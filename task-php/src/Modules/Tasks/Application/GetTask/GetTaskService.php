<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\GetTask;

use App\Core\Application\Services\AbstractAppService;

use App\Modules\Tasks\Domain\Enums\TasksMessageEnum;
use App\Modules\Tasks\Domain\Exceptions\TasksException;
use App\Modules\Tasks\Infrastructure\Repositories\TasksReaderSqliteRepository;

final class GetTaskService extends AbstractAppService
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
        if (!$taskEntity = $this->tasksReaderSqliteRepository->getTaskByTaskUuid($taskUuid)) {
            TasksException::notFoundCustom(__(TasksMessageEnum::TASK_X_NOT_FOUND, $taskUuid));
        }

        return GetTaskResultDto::fromPrimitives(["taskEntity" => $taskEntity]);
    }
}
