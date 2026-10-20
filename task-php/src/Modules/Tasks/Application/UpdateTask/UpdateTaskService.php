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

    private DateTimer $dateTimer;
    private TasksReaderSqliteRepository $tasksReaderSqliteRepository;
    private TasksWriterSqliteRepository $tasksWriterSqliteRepository;

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
        $taskUuid = $updateTaskDto->getTaskUuid();
        // una task de otro usuario responde igual que si no existiera (no se revela que existe)
        $taskEntity = $this->tasksReaderSqliteRepository->getTaskByTaskUuid($taskUuid);
        if (!$taskEntity || $taskEntity->getUserId() !== $updateTaskDto->getAuthUserId()) {
            TasksException::notFoundCustom(__(TasksMessageEnum::TASK_X_NOT_FOUND, $taskUuid));
        }

        // reemplazo completo sobre la task actual: se conservan id, propietario y created_at
        $taskEntityToUpdate = TaskEntity::fromPrimitives([
            TasksKeyEnum::ID => $taskEntity->getId(),
            TasksKeyEnum::USER_ID => $taskEntity->getUserId(),
            TasksKeyEnum::USER_UUID => $taskEntity->getUserUuid(),
            TasksKeyEnum::TASK_UUID => $taskUuid,
            TasksKeyEnum::TASK_TITLE => $updateTaskDto->getTaskTitle(),
            TasksKeyEnum::TASK_STATUS => $updateTaskDto->getTaskStatus() ?: TaskStatusEnum::DEFAULT,
            TasksKeyEnum::TASK_TYPE => $updateTaskDto->getTaskType() ?: TaskTypeEnum::DEFAULT,
            TasksKeyEnum::TARGET_DATE => $updateTaskDto->getTargetDate(),
            TasksKeyEnum::TASK_DESCRIPTION => $updateTaskDto->getTaskDescription(),
            TasksKeyEnum::CREATED_AT => $taskEntity->getCreatedAt(),
            TasksKeyEnum::UPDATED_AT => $this->dateTimer->getNowDtIntoTargetTz(),
        ]);

        if ($errors = TaskFieldsValidator::getInstance()->getErrors($taskEntityToUpdate)) {
            FieldsException::badRequestCustom(
                $this->getErrorStackMessage(
                    __(SharedMessageEnum::FIELDS_VALIDATION_ERRORS), $errors
                ));
        }

        $this->tasksWriterSqliteRepository->updateTaskById($taskEntityToUpdate);

        return UpdateTaskResultDto::fromPrimitives([
            "task" => $this->tasksReaderSqliteRepository->getTaskById($taskEntity->getId())?->toPrimitives() ?? [],
        ]);
    }
}
