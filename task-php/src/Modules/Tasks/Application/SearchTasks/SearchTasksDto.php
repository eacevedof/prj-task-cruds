<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\SearchTasks;

use App\Modules\Shared\Application\AbstractInputDto;

use App\Modules\Tasks\Domain\Services\TasksSearchValidator;

final readonly class SearchTasksDto extends AbstractInputDto
{
    private int $authUserId;
    private string $authUserUuid;
    private string $userUuid;
    private string $taskText;
    private string $taskStatus;
    private string $taskType;
    private string $targetDateFrom;
    private string $targetDateTo;
    private int $page;
    private int $pageSize;

    /**
     * userUuid vacio = las del usuario autenticado. Sin page/pageSize: primera pagina con el tamaño por defecto.
     */
    public function __construct(array $primitives)
    {
        parent::__construct($primitives);

        $this->authUserId = (int) ($primitives["authUserId"] ?? 0);
        $this->authUserUuid = trim((string) ($primitives["authUserUuid"] ?? ""));
        $this->userUuid = trim((string) ($primitives["userUuid"] ?? ""));
        $this->taskText = trim((string) ($primitives["taskText"] ?? ""));
        $this->taskStatus = trim((string) ($primitives["taskStatus"] ?? ""));
        $this->taskType = trim((string) ($primitives["taskType"] ?? ""));
        $this->targetDateFrom = trim((string) ($primitives["targetDateFrom"] ?? ""));
        $this->targetDateTo = trim((string) ($primitives["targetDateTo"] ?? ""));
        $this->page = (int) ($primitives["page"] ?? TasksSearchValidator::PAGE_MIN);
        $this->pageSize = (int) ($primitives["pageSize"] ?? TasksSearchValidator::PAGE_SIZE_DEFAULT);
    }

    public static function fromPrimitives(array $primitives): self
    {
        return new self($primitives);
    }

    public function getAuthUserId(): int
    {
        return $this->authUserId;
    }

    public function getAuthUserUuid(): string
    {
        return $this->authUserUuid;
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    public function getTaskText(): string
    {
        return $this->taskText;
    }

    public function getTaskStatus(): string
    {
        return $this->taskStatus;
    }

    public function getTaskType(): string
    {
        return $this->taskType;
    }

    public function getTargetDateFrom(): string
    {
        return $this->targetDateFrom;
    }

    public function getTargetDateTo(): string
    {
        return $this->targetDateTo;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }
}
