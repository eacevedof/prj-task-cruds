<?php

declare(strict_types=1);

namespace Tests\Modules\Shared\Infrastructure\Repositories;

use App\Modules\Devops\Application\RunMigrations\RunMigrationsDto;
use App\Modules\Devops\Application\RunMigrations\RunMigrationsService;
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
        // RunMigrationsService hace echo de cada paso: fuera de la salida de phpunit
        ob_start();
        RunMigrationsService::getInstance()->__invoke(RunMigrationsDto::fromPrimitives([]));
        ob_end_clean();

        $this->insertUsers();
        $this->insertTasks();
    }

    private function insertUsers(): void
    {
        $users = [
            [UsersRawRepositoryMock::USER_UUID_OK, UsersRawRepositoryMock::USER_NAME_OK, UsersRawRepositoryMock::USER_EMAIL_OK],
            [UsersRawRepositoryMock::USER_UUID_TO_UPDATE, "Luis Test", UsersRawRepositoryMock::USER_EMAIL_TO_UPDATE],
            [UsersRawRepositoryMock::USER_UUID_TO_DELETE, "Eva Test", UsersRawRepositoryMock::USER_EMAIL_TO_DELETE],
        ];
        $sql = "
        -- insertUsers (fixtures)
        INSERT INTO users (user_uuid, user_name, user_email, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?)
        ";
        foreach ($users as [$userUuid, $userName, $userEmail]) {
            $this->execute($sql, [$userUuid, $userName, $userEmail, self::NOW, self::NOW]);
        }
    }

    private function insertTasks(): void
    {
        $tasks = [
            [TasksRawRepositoryMock::TASK_UUID_OK, TasksRawRepositoryMock::TASK_TITLE_OK],
            [TasksRawRepositoryMock::TASK_UUID_TO_UPDATE, "Llamar al banco"],
            [TasksRawRepositoryMock::TASK_UUID_TO_DELETE, "Tirar la basura"],
        ];
        $sql = "
        -- insertTasks (fixtures)
        INSERT INTO tasks (user_id, task_uuid, task_title, created_at, updated_at)
        VALUES ((SELECT id FROM users WHERE user_uuid = ?), ?, ?, ?, ?)
        ";
        foreach ($tasks as [$taskUuid, $taskTitle]) {
            $this->execute($sql, [UsersRawRepositoryMock::USER_UUID_OK, $taskUuid, $taskTitle, self::NOW, self::NOW]);
        }
    }
}
