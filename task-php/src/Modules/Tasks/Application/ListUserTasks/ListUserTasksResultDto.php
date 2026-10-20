<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\ListUserTasks;

final readonly class ListUserTasksResultDto
{
    /**
     * lista de TaskEntity::toPrimitives
     */
    private array $tasks;

    public function __construct(array $primitives)
    {
        $this->tasks = $primitives["tasks"] ?? [];
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    public function getTasks(): array
    {
        return $this->tasks;
    }

    public function toArray(): array
    {
        return $this->tasks;
    }
}
