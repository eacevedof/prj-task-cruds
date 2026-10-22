<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\CreateTask;

use App\Core\Application\Services\AbstractAppService;

use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Components\Uuid\UuidComponent;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;

use App\Modules\Tasks\Domain\Enums\TasksMessageEnum;
use App\Modules\Tasks\Domain\Enums\TaskStatusEnum;
use App\Modules\Tasks\Domain\Enums\TaskTypeEnum;
use App\Modules\Tasks\Domain\Exceptions\TasksException;
use App\Modules\Tasks\Domain\Validators\TaskFieldsValidator;
use App\Modules\Tasks\Infrastructure\Repositories\TasksReaderSqliteRepository;
use App\Modules\Tasks\Infrastructure\Repositories\TasksWriterSqliteRepository;

/**
 * Alta de una task para el usuario user_uuid. task_status / task_type vacios -> su DEFAULT.
 */
final class CreateTaskService extends AbstractAppService
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
    public function __invoke(CreateTaskDto $createTaskDto): CreateTaskResultDto
    {
        $userUuid = $createTaskDto->getUserUuid();
        if (!$userId = $this->tasksReaderSqliteRepository->getUserIdByUserUuid($userUuid)) {
            TasksException::notFoundCustom(__(TasksMessageEnum::USER_X_NOT_FOUND, $userUuid));
        }

        $taskTitle = $createTaskDto->getTaskTitle();
        $taskStatus = $createTaskDto->getTaskStatus() ?: TaskStatusEnum::DEFAULT;
        $taskType = $createTaskDto->getTaskType() ?: TaskTypeEnum::DEFAULT;
        $targetDate = $createTaskDto->getTargetDate();
        $taskDescription = $createTaskDto->getTaskDescription();

        if ($errors = TaskFieldsValidator::getInstance()->getErrors(
            $taskTitle, $taskStatus, $taskType, $targetDate, $taskDescription
        )) {
            $this->setErrors($errors);
            FieldsException::badRequestCustom(__(SharedMessageEnum::FIELDS_VALIDATION_ERRORS));
        }

        $taskUuid = UuidComponent::getInstance()->getUuidV4();
        $this->tasksWriterSqliteRepository->insertTask(
            $userId, $taskUuid, $taskTitle, $taskStatus, $taskType, $targetDate, $taskDescription, $this->getUtcNow()
        );

        return CreateTaskResultDto::fromPrimitives([
            "taskEntity" => $this->tasksReaderSqliteRepository->getTaskByTaskUuid($taskUuid),
        ]);
    }
}
