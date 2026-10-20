<?php
declare(strict_types=1);

namespace App\Modules\Tasks\Infrastructure\Controllers;

use Throwable;

use GraphQL\Error\Error;

use App\Modules\Shared\Infrastructure\Controllers\AbstractGraphqlController;
use App\Modules\Shared\Infrastructure\Exceptions\FieldsException;

use App\Modules\Users\Domain\Exceptions\UsersException;

use App\Modules\Tasks\Domain\Enums\TasksKeyEnum;
use App\Modules\Tasks\Application\CreateTask\CreateTaskDto;
use App\Modules\Tasks\Application\CreateTask\CreateTaskService;
use App\Modules\Tasks\Application\DeleteTask\DeleteTaskDto;
use App\Modules\Tasks\Application\DeleteTask\DeleteTaskService;
use App\Modules\Tasks\Application\GetTask\GetTaskDto;
use App\Modules\Tasks\Application\GetTask\GetTaskService;
use App\Modules\Tasks\Application\SearchTasks\SearchTasksDto;
use App\Modules\Tasks\Application\SearchTasks\SearchTasksService;
use App\Modules\Tasks\Application\UpdateTask\UpdateTaskDto;
use App\Modules\Tasks\Application\UpdateTask\UpdateTaskService;
use App\Modules\Tasks\Domain\Exceptions\TasksException;

/**
 * Resolvers GraphQL de tasks (src/Core/Graphql/schema.graphql): los mismos casos de uso que la API REST.
 * El 401 de la autenticacion llega como UsersException.
 */
final class TasksGraphqlController extends AbstractGraphqlController
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * Query tasks (userUuid "") y userTasks / User.tasks: busqueda paginada con filtros.
     * @param array $args {filter?: {task_text?, task_status?, task_type?, target_date_from?, target_date_to?}, page?, page_size?}
     * @throws Error
     */
    public function searchTasks(string $userUuid, array $args): array
    {
        try {
            $filter = $args[TasksKeyEnum::FILTER] ?? [];
            return SearchTasksService::getInstance()->__invoke(
                SearchTasksDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "userUuid" => $userUuid,
                    "taskText" => $filter[TasksKeyEnum::TASK_TEXT] ?? "",
                    "taskStatus" => $filter[TasksKeyEnum::TASK_STATUS] ?? "",
                    "taskType" => $filter[TasksKeyEnum::TASK_TYPE] ?? "",
                    "targetDateFrom" => $filter[TasksKeyEnum::TARGET_DATE_FROM] ?? "",
                    "targetDateTo" => $filter[TasksKeyEnum::TARGET_DATE_TO] ?? "",
                    "page" => $args[TasksKeyEnum::PAGE] ?? null,
                    "pageSize" => $args[TasksKeyEnum::PAGE_SIZE] ?? null,
                ])
            )->toArray();
        }
        catch (FieldsException | UsersException | TasksException $e) {
            throw $this->getGraphqlDomainError($e);
        }
        catch (Throwable $e) {
            throw $this->getGraphqlUnexpectedError($e, __METHOD__);
        }
    }

    /**
     * @throws Error
     */
    public function getTask(array $args): array
    {
        try {
            return GetTaskService::getInstance()->__invoke(
                GetTaskDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "taskUuid" => $args[TasksKeyEnum::TASK_UUID] ?? "",
                ])
            )->toArray();
        }
        catch (UsersException | TasksException $e) {
            throw $this->getGraphqlDomainError($e);
        }
        catch (Throwable $e) {
            throw $this->getGraphqlUnexpectedError($e, __METHOD__);
        }
    }

    /**
     * @throws Error
     */
    public function createTask(array $args): array
    {
        try {
            return CreateTaskService::getInstance()->__invoke(
                CreateTaskDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "taskTitle" => $args[TasksKeyEnum::TASK_TITLE] ?? "",
                    "taskStatus" => $args[TasksKeyEnum::TASK_STATUS] ?? "",
                    "taskType" => $args[TasksKeyEnum::TASK_TYPE] ?? "",
                    "targetDate" => $args[TasksKeyEnum::TARGET_DATE] ?? "",
                    "taskDescription" => $args[TasksKeyEnum::TASK_DESCRIPTION] ?? "",
                ])
            )->toArray();
        }
        catch (FieldsException | UsersException $e) {
            throw $this->getGraphqlDomainError($e);
        }
        catch (Throwable $e) {
            throw $this->getGraphqlUnexpectedError($e, __METHOD__);
        }
    }

    /**
     * @throws Error
     */
    public function updateTask(array $args): array
    {
        try {
            return UpdateTaskService::getInstance()->__invoke(
                UpdateTaskDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "taskUuid" => $args[TasksKeyEnum::TASK_UUID] ?? "",
                    "taskTitle" => $args[TasksKeyEnum::TASK_TITLE] ?? "",
                    "taskStatus" => $args[TasksKeyEnum::TASK_STATUS] ?? "",
                    "taskType" => $args[TasksKeyEnum::TASK_TYPE] ?? "",
                    "targetDate" => $args[TasksKeyEnum::TARGET_DATE] ?? "",
                    "taskDescription" => $args[TasksKeyEnum::TASK_DESCRIPTION] ?? "",
                ])
            )->toArray();
        }
        catch (FieldsException | UsersException | TasksException $e) {
            throw $this->getGraphqlDomainError($e);
        }
        catch (Throwable $e) {
            throw $this->getGraphqlUnexpectedError($e, __METHOD__);
        }
    }

    /**
     * @throws Error
     */
    public function deleteTask(array $args): array
    {
        try {
            return DeleteTaskService::getInstance()->__invoke(
                DeleteTaskDto::fromPrimitives([
                    ...$this->httpRequest->getMetadata(),
                    ...$this->getAuthUserOrFail(),
                    "taskUuid" => $args[TasksKeyEnum::TASK_UUID] ?? "",
                ])
            )->toArray();
        }
        catch (UsersException | TasksException $e) {
            throw $this->getGraphqlDomainError($e);
        }
        catch (Throwable $e) {
            throw $this->getGraphqlUnexpectedError($e, __METHOD__);
        }
    }
}
