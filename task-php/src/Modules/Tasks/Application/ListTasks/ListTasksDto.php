<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\ListTasks;

final readonly class ListTasksDto
{
    public static function fromPrimitives(array $primitives = []): self
    {
        return new self();
    }
}
