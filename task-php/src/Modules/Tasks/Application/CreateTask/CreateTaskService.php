<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Application\CreateTask;

use App\Modules\Shared\Domain\Enums\SharedMessageEnum;
use App\Modules\Shared\Infrastructure\Components\Date\DateTimer;
use App\Modules\Shared\Infrastructure\Components\Uuid\UuidGenerator;
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
 * El dueño es siempre el usuario autenticado. task_status / task_type vacios -> su DEFAULT.
 */
final class CreateTaskService
{
    use ErrorStackTrait;

    private readonly DateTimer $dateTimer;
    private readonly TasksReaderSqliteRepository $tasksReaderSqliteRepository;
    private readonly TasksWriterSqliteRepository $tasksWriterSqliteRepository;

    private CreateTaskDto $createTaskDto;
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
    public function __invoke(
        CreateTaskDto $createTaskDto
    ): CreateTaskResultDto
    {
        $this->createTaskDto = $createTaskDto;
        $this->failIfWrongInput();

        $this->taskEntity = TaskEntity::fromPrimitives([
            TasksKeyEnum::USER_ID => $createTaskDto->getAuthUserId(),
            TasksKeyEnum::USER_UUID => $createTaskDto->getAuthUserUuid(),
            TasksKeyEnum::TASK_UUID => UuidGenerator::getInstance()->getUniqueIdWithPrefix(TaskEntity::TASK_UUID_PREFIX),
            TasksKeyEnum::TASK_TITLE => $createTaskDto->getTaskTitle(),
            TasksKeyEnum::TASK_STATUS => $createTaskDto->getTaskStatus() ?: TaskStatusEnum::DEFAULT,
            TasksKeyEnum::TASK_TYPE => $createTaskDto->getTaskType() ?: TaskTypeEnum::DEFAULT,
            TasksKeyEnum::TARGET_DATE => $createTaskDto->getTargetDate(),
            TasksKeyEnum::TASK_DESCRIPTION => $createTaskDto->getTaskDescription(),
            TasksKeyEnum::CREATED_AT => $this->dateTimer->getNowDtIntoTargetTz(),
        ]);

        $this->failIfWrongFields();

        $taskId = $this->tasksWriterSqliteRepository->insertTask($this->taskEntity);

        return CreateTaskResultDto::fromPrimitives([
            "task" => $this->tasksReaderSqliteRepository->getTaskById($taskId)?->toPrimitives() ?? [],
        ]);
    }

    /**
     * Por API nunca llega sin usuario (el controller corta con 401); protege otras entradas (consola, tests).
     * @throws TasksException
     */
    private function failIfWrongInput(): void
    {
        if ($this->createTaskDto->getAuthUserId() < 1) {
            TasksException::unauthorizedCustom(__(TasksMessageEnum::MISSING_OR_INVALID_API_TOKEN));
        }
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
