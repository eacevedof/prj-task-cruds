<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\ListTasks;

use App\Modules\Tasks\Domain\Entities\TaskEntity;
use App\Modules\Tasks\Infrastructure\Repositories\TasksReaderSqliteRepository;

final class ListTasksService
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

    public function __invoke(ListTasksDto $listTasksDto): ListTasksResultDto
    {
        // solo las del usuario autenticado
        return ListTasksResultDto::fromPrimitives([
            "tasks" => array_map(
                fn(TaskEntity $taskEntity): array => $taskEntity->toPrimitives(),
                $this->tasksReaderSqliteRepository->getTasksByUserId($listTasksDto->getAuthUserId())
            ),
        ]);
    }
}
