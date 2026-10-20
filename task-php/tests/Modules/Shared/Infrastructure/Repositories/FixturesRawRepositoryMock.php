<?php

declare(strict_types=1);

namespace Tests\Modules\Shared\Infrastructure\Repositories;

use App\Modules\Devops\Application\RunMigrations\RunMigrationsDto;
use App\Modules\Devops\Application\RunMigrations\RunMigrationsService;
use App\Modules\Shared\Infrastructure\Components\Password\PasswordHasher;
use App\Modules\Shared\Infrastructure\Components\Token\TokenGenerator;
use App\Modules\Shared\Infrastructure\Repositories\AbstractSqliteRepository;

/**
 * BD de tests desde cero: las migraciones reales de database/migrations + los fixtures de
 * UsersRawRepositoryMock y TasksRawRepositoryMock. Lo lanza tests/bootstrap.php una vez por ejecucion.
 */
final class FixturesRawRepositoryMock extends AbstractSqliteRepository
{
    private const string NOW = "2026-01-01 00:00:00";

    public static function getInstance(): self
    {
        return new self();
    }

    public function createDbWithFixtures(): void
    {
        RunMigrationsService::getInstance()->__invoke(RunMigrationsDto::fromPrimitives([]));

        $this->insertUsers();
        $this->insertTasks();
    }

    private function insertUsers(): void
    {
        $users = [
            [UsersRawRepositoryMock::USER_UUID_OK, UsersRawRepositoryMock::USER_NAME_OK, UsersRawRepositoryMock::USER_EMAIL_OK, UsersRawRepositoryMock::USER_API_TOKEN_OK, ""],
            [UsersRawRepositoryMock::USER_UUID_TO_UPDATE, "luis.test", UsersRawRepositoryMock::USER_EMAIL_TO_UPDATE, UsersRawRepositoryMock::USER_API_TOKEN_TO_UPDATE, ""],
            [UsersRawRepositoryMock::USER_UUID_TO_DELETE, "eva.test", UsersRawRepositoryMock::USER_EMAIL_TO_DELETE, UsersRawRepositoryMock::USER_API_TOKEN_TO_DELETE, ""],
            [UsersRawRepositoryMock::USER_UUID_TO_REGENERATE, "rosa.test", UsersRawRepositoryMock::USER_EMAIL_TO_REGENERATE, UsersRawRepositoryMock::USER_API_TOKEN_TO_REGENERATE, ""],
            [UsersRawRepositoryMock::USER_UUID_SEARCH, "sara.test", UsersRawRepositoryMock::USER_EMAIL_SEARCH, UsersRawRepositoryMock::USER_API_TOKEN_SEARCH, ""],
            [UsersRawRepositoryMock::USER_UUID_TO_LOG_IN, UsersRawRepositoryMock::USER_NAME_TO_LOG_IN, UsersRawRepositoryMock::USER_EMAIL_TO_LOG_IN, UsersRawRepositoryMock::USER_API_TOKEN_TO_LOG_IN, UsersRawRepositoryMock::USER_PASSWORD_TO_LOG_IN],
        ];
        $sql = "
        -- insertUsers (fixtures)
        INSERT INTO `users`
            (user_uuid, user_name, user_email, password_hash, api_token_hash, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?)
        ";
        $passwordHasher = PasswordHasher::getInstance();
        $tokenGenerator = TokenGenerator::getInstance();
        foreach ($users as [$userUuid, $userName, $userEmail, $apiToken, $password]) {
            $passwordHash = $password ? $passwordHasher->getPasswordHash($password) : null;
            $this->execute($sql, [$userUuid, $userName, $userEmail, $passwordHash, $tokenGenerator->getTokenHash($apiToken), self::NOW, self::NOW]);
        }
    }

    private function insertTasks(): void
    {
        $tasks = [
            [TasksRawRepositoryMock::TASK_UUID_OK, TasksRawRepositoryMock::TASK_TITLE_OK],
            [TasksRawRepositoryMock::TASK_UUID_TO_UPDATE, "Llamar al banco"],
            [TasksRawRepositoryMock::TASK_UUID_TO_DELETE, "Tirar la basura"],
            [TasksRawRepositoryMock::TASK_UUID_TO_DELETE_GRAPHQL, "Regar las plantas"],
        ];
        $sql = "
        -- insertTasks (fixtures)
        INSERT INTO `tasks`
            (user_id, task_uuid, task_title, created_at, updated_at)
        VALUES
            ((SELECT id FROM `users` WHERE 1=1 AND user_uuid = ?), ?, ?, ?, ?)
        ";
        foreach ($tasks as [$taskUuid, $taskTitle]) {
            $this->execute($sql, [UsersRawRepositoryMock::USER_UUID_OK, $taskUuid, $taskTitle, self::NOW, self::NOW]);
        }
        $this->insertSearchTasks();
    }

    /**
     * Las de la busqueda paginada: en este orden, asi que id desc = gamma, beta, alpha.
     */
    private function insertSearchTasks(): void
    {
        $tasks = [
            [TasksRawRepositoryMock::SEARCH_TITLE_ALPHA, "pending", "work", TasksRawRepositoryMock::SEARCH_TARGET_DATE_ALPHA, "first"],
            [TasksRawRepositoryMock::SEARCH_TITLE_BETA, "done", "personal", TasksRawRepositoryMock::SEARCH_TARGET_DATE_BETA, "second"],
            [TasksRawRepositoryMock::SEARCH_TITLE_GAMMA, "pending", "other", null, TasksRawRepositoryMock::SEARCH_DESCRIPTION_GAMMA],
        ];
        $sql = "
        -- insertSearchTasks (fixtures)
        INSERT INTO `tasks`
            (user_id, task_uuid, task_title, task_status, task_type, target_date, task_description, created_at, updated_at)
        VALUES
            ((SELECT id FROM `users` WHERE 1=1 AND user_uuid = ?), ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        foreach ($tasks as $i => [$taskTitle, $taskStatus, $taskType, $targetDate, $taskDescription]) {
            $this->execute($sql, [
                UsersRawRepositoryMock::USER_UUID_SEARCH, sprintf("eeeeeeee-eeee-4eee-8eee-%012d", $i),
                $taskTitle, $taskStatus, $taskType, $targetDate, $taskDescription, self::NOW, self::NOW,
            ]);
        }
    }
}
