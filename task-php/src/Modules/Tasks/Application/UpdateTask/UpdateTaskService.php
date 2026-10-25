<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\UpdateTask;

use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Components\Date\DateTimer;
use App\Modules\Shared\Infrastructure\Traits\ErrorStackTrait;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;

use App\Modules\Tasks\Domain\Entities\TaskEntity;
use App\Modules\Tasks\Domain\Enums\TaskStatusEnum;
use App\Modules\Tasks\Domain\Enums\TaskTypeEnum;
use App\Modules\Tasks\Domain\Enums\TasksKeyEnum;
use App\Modules\Tasks\Domain\Enums\TasksMessageEnum;
use App\Modules\Tasks\Domain\Services\TaskFieldsValidator;
use App\Modules\Tasks\Infrastructure\Repositories\TasksReaderSqliteRepository;
use App\Modules\Tasks\Infrastructure\Repositories\TasksWriterSqliteRepository;
use App\Modules\Tasks\Domain\Exceptions\TasksException;

/**
 * PUT: reemplazo completo (mismas reglas y defaults que el alta). El propietario no cambia.
 */
final class UpdateTaskService
{
    use ErrorStackTrait;

    private readonly DateTimer $dateTimer;
    private readonly TasksReaderSqliteRepository $tasksReaderSqliteRepository;
    private readonly TasksWriterSqliteRepository $tasksWriterSqliteRepository;

    private UpdateTaskDto $updateTaskDto;
    private TaskEntity $taskEntity;

    public function __construct()
    {
        $this->dateTimer = DateTimer::getInstance();
        $this->tasksReaderSqliteRepository = TasksReaderSqliteRepository::getInstance();
        $this->tasksWriterSqliteRepository = TasksWriterSqliteRepository::getInstance();
    }

    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * @throws FieldsException
     * @throws TasksException
     */
    public function __invoke(UpdateTaskDto $updateTaskDto): UpdateTaskResultDto
    {
        $this->updateTaskDto = $updateTaskDto;
        $this->failIfWrongInput();

        $currentTaskEntity = $this->tryToGetOwnTaskOrFail();
        // reemplazo completo sobre la task actual: se conservan id, propietario y created_at
        $this->taskEntity = TaskEntity::fromPrimitives([
            TasksKeyEnum::ID => $currentTaskEntity->getId(),
            TasksKeyEnum::USER_ID => $currentTaskEntity->getUserId(),
            TasksKeyEnum::USER_UUID => $currentTaskEntity->getUserUuid(),
            TasksKeyEnum::TASK_UUID => $currentTaskEntity->getTaskUuid(),
            TasksKeyEnum::TASK_TITLE => $updateTaskDto->getTaskTitle(),
            TasksKeyEnum::TASK_STATUS => $updateTaskDto->getTaskStatus() ?: TaskStatusEnum::DEFAULT,
            TasksKeyEnum::TASK_TYPE => $updateTaskDto->getTaskType() ?: TaskTypeEnum::DEFAULT,
            TasksKeyEnum::TARGET_DATE => $updateTaskDto->getTargetDate(),
            TasksKeyEnum::TASK_DESCRIPTION => $updateTaskDto->getTaskDescription(),
            TasksKeyEnum::CREATED_AT => $currentTaskEntity->getCreatedAt(),
            TasksKeyEnum::UPDATED_AT => $this->dateTimer->getNowDtIntoTargetTz(),
        ]);

        $this->failIfWrongFields();

        $this->tasksWriterSqliteRepository->updateTaskById($this->taskEntity);

        return UpdateTaskResultDto::fromPrimitives([
            "task" => $this->tasksReaderSqliteRepository->getTaskById($this->taskEntity->getId())?->toPrimitives() ?? [],
        ]);
    }

    /**
     * @throws TasksException
     */
    private function failIfWrongInput(): void
    {
        if ($this->updateTaskDto->getAuthUserId() < 1) {
            TasksException::unauthorizedCustom(__(TasksMessageEnum::MISSING_OR_INVALID_API_TOKEN));
        }
    }

    /**
     * Una task de otro usuario responde igual que si no existiera (no se revela que existe).
     * @throws TasksException
     */
    private function tryToGetOwnTaskOrFail(): TaskEntity
    {
        $taskUuid = $this->updateTaskDto->getTaskUuid();
        $taskEntity = $this->tasksReaderSqliteRepository->getTaskByTaskUuid($taskUuid);
        if (!$taskEntity || $taskEntity->getUserId() !== $this->updateTaskDto->getAuthUserId()) {
            TasksException::notFoundCustom(__(TasksMessageEnum::TASK_X_NOT_FOUND, $taskUuid));
        }
        return $taskEntity;
    }

    /**
     * @throws FieldsException
     */
    private function failIfWrongFields(): void
    {
        if ($errors = TaskFieldsValidator::getInstance()->getErrors($this->taskEntity)) {
            FieldsException::badRequestCustom(
                $this->getErrorStackMessage(
                    __(SharedMessageEnum::FIELDS_VALIDATION_ERRORS), $errors
                ));
        }
    }
}
