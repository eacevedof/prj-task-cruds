<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\GetTask;

use App\Modules\Tasks\Domain\Entities\TaskEntity;

final readonly class GetTaskResultDto
{
    public function __construct(
        private TaskEntity $taskEntity,
    ) {
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives["taskEntity"]);
    }

    public function getTaskEntity(): TaskEntity
    {
        return $this->taskEntity;
    }

    public function toArray(): array
    {
        return $this->taskEntity->toPrimitives();
    }
}
