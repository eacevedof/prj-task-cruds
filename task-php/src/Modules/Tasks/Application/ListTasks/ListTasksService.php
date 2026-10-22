<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\ListTasks;

use App\Core\Application\Services\AbstractAppService;

use App\Modules\Tasks\Infrastructure\Repositories\TasksReaderSqliteRepository;

final class ListTasksService extends AbstractAppService
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
        return ListTasksResultDto::fromPrimitives([
            "taskEntities" => $this->tasksReaderSqliteRepository->getTasks(),
        ]);
    }
}
