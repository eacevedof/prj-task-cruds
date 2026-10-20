<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\SearchTasks;

use App\Modules\Tasks\Domain\Enums\TasksKeyEnum;

final readonly class SearchTasksResultDto
{
    /**
     * la pagina pedida: lista de TaskEntity::toPrimitives
     */
    private array $tasks;
    private int $page;
    private int $pageSize;
    private int $totalItems;
    private int $totalPages;

    public function __construct(array $primitives)
    {
        $this->tasks = $primitives["tasks"] ?? [];
        $this->page = (int) ($primitives["page"] ?? 0);
        $this->pageSize = (int) ($primitives["pageSize"] ?? 0);
        $this->totalItems = (int) ($primitives["totalItems"] ?? 0);
        $this->totalPages = (int) ($primitives["totalPages"] ?? 0);
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    public function getTasks(): array
    {
        return $this->tasks;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    /**
     * Tipo TaskPage de schema.graphql.
     */
    public function toArray(): array
    {
        return [
            TasksKeyEnum::ITEMS => $this->tasks,
            TasksKeyEnum::PAGE => $this->page,
            TasksKeyEnum::PAGE_SIZE => $this->pageSize,
            TasksKeyEnum::TOTAL_ITEMS => $this->totalItems,
            TasksKeyEnum::TOTAL_PAGES => $this->totalPages,
        ];
    }
}
