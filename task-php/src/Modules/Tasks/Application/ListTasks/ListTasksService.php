<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\ListTasks;

use App\Modules\Tasks\Domain\Entities\TaskEntity;
use App\Modules\Tasks\Domain\Enums\TasksMessageEnum;
use App\Modules\Tasks\Infrastructure\Repositories\TasksReaderSqliteRepository;
use App\Modules\Tasks\Domain\Exceptions\TasksException;

final class ListTasksService
{
    private readonly TasksReaderSqliteRepository $tasksReaderSqliteRepository;

    private ListTasksDto $listTasksDto;

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
    public function __invoke(ListTasksDto $listTasksDto): ListTasksResultDto
    {
        $this->listTasksDto = $listTasksDto;
        $this->failIfWrongInput();

        return ListTasksResultDto::fromPrimitives([
            "tasks" => array_map(
                fn(TaskEntity $taskEntity): array => $taskEntity->toPrimitives(),
                $this->tasksReaderSqliteRepository->getTasksByUserId($listTasksDto->getAuthUserId())
            ),
        ]);
    }

    /**
     * @throws TasksException
     */
    private function failIfWrongInput(): void
    {
        if ($this->listTasksDto->getAuthUserId() < 1) {
            TasksException::unauthorizedCustom(__(TasksMessageEnum::MISSING_OR_INVALID_API_TOKEN));
        }
    }
}
