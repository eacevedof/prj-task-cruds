<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\ListTasks;

use App\Modules\Tasks\Domain\Entities\TaskEntity;

final readonly class ListTasksResultDto
{
    /** @param TaskEntity[] $taskEntities */
    public function __construct(
        private array $taskEntities,
    ) {
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives["taskEntities"] ?? []);
    }

    /** @return TaskEntity[] */
    public function getTaskEntities(): array
    {
        return $this->taskEntities;
    }

    public function toArray(): array
    {
        return array_map(
            fn(TaskEntity $taskEntity) => $taskEntity->toPrimitives(),
            $this->taskEntities
        );
    }
}
