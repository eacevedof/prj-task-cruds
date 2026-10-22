<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\UpdateTask;

use App\Core\Application\Services\AbstractAppService;

use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;

use App\Modules\Tasks\Domain\Enums\TasksMessageEnum;
use App\Modules\Tasks\Domain\Enums\TaskStatusEnum;
use App\Modules\Tasks\Domain\Enums\TaskTypeEnum;
use App\Modules\Tasks\Domain\Exceptions\TasksException;
use App\Modules\Tasks\Domain\Validators\TaskFieldsValidator;
use App\Modules\Tasks\Infrastructure\Repositories\TasksReaderSqliteRepository;
use App\Modules\Tasks\Infrastructure\Repositories\TasksWriterSqliteRepository;

/**
 * PUT: reemplazo completo (mismas reglas y defaults que el alta). El propietario no cambia.
 */
final class UpdateTaskService extends AbstractAppService
{
    private TasksReaderSqliteRepository $tasksReaderSqliteRepository;
    private TasksWriterSqliteRepository $tasksWriterSqliteRepository;

    public function __construct()
    {
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
        if (!$this->tasksReaderSqliteRepository->getTaskByTaskUuid($taskUuid)) {
            TasksException::notFoundCustom(__(TasksMessageEnum::TASK_X_NOT_FOUND, $taskUuid));
        }

        $taskTitle = $updateTaskDto->getTaskTitle();
        $taskStatus = $updateTaskDto->getTaskStatus() ?: TaskStatusEnum::DEFAULT;
        $taskType = $updateTaskDto->getTaskType() ?: TaskTypeEnum::DEFAULT;
        $targetDate = $updateTaskDto->getTargetDate();
        $taskDescription = $updateTaskDto->getTaskDescription();

        if ($errors = TaskFieldsValidator::getInstance()->getErrors(
            $taskTitle, $taskStatus, $taskType, $targetDate, $taskDescription
        )) {
            $this->setErrors($errors);
            FieldsException::badRequestCustom(__(SharedMessageEnum::FIELDS_VALIDATION_ERRORS));
        }

        $this->tasksWriterSqliteRepository->updateTaskByTaskUuid(
            $taskUuid, $taskTitle, $taskStatus, $taskType, $targetDate, $taskDescription, $this->getUtcNow()
        );

        return UpdateTaskResultDto::fromPrimitives([
            "taskEntity" => $this->tasksReaderSqliteRepository->getTaskByTaskUuid($taskUuid),
        ]);
    }
}
