<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\GetTask;

final readonly class GetTaskResultDto
{
    /**
     * la task (TaskEntity::toPrimitives)
     */
    private array $task;

    public function __construct(array $primitives)
    {
        $this->task = $primitives["task"] ?? [];
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    public function getTask(): array
    {
        return $this->task;
    }

    public function toArray(): array
    {
        return $this->task;
    }
}
