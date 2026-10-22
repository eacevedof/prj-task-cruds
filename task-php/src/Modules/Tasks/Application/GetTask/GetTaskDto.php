<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\GetTask;

final readonly class GetTaskDto
{
    public function __construct(
        private string $taskUuid,
    ) {
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self(
            trim((string) ($primitives["taskUuid"] ?? "")),
        );
    }

    public function getTaskUuid(): string
    {
        return $this->taskUuid;
    }
}
