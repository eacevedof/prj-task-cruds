import { RunMigrationsDto } from "#app/Modules/Devops/Application/RunMigrations/RunMigrationsDto.ts";
import { RunMigrationsService } from "#app/Modules/Devops/Application/RunMigrations/RunMigrationsService.ts";
import { PasswordHasher } from "#app/Modules/Shared/Infrastructure/Components/Password/PasswordHasher.ts";
import { TokenGenerator } from "#app/Modules/Shared/Infrastructure/Components/Token/TokenGenerator.ts";
import { AbstractSqliteRepository } from "#app/Modules/Shared/Infrastructure/Repositories/AbstractSqliteRepository.ts";

import { TasksRawRepositoryMock } from "#tests/Modules/Shared/Infrastructure/Repositories/TasksRawRepositoryMock.ts";
import { UsersRawRepositoryMock } from "#tests/Modules/Shared/Infrastructure/Repositories/UsersRawRepositoryMock.ts";

const NOW = "2026-01-01 00:00:00";

/**
 * BD de tests desde cero: las migraciones reales de database/migrations + los fixtures de
 * UsersRawRepositoryMock y TasksRawRepositoryMock. Lo lanza tests/bootstrap.ts una vez por fichero de test.
 */
export class FixturesRawRepositoryMock extends AbstractSqliteRepository {

    static getInstance(): FixturesRawRepositoryMock {
        return new FixturesRawRepositoryMock();
    }

    createDbWithFixtures(): void {
        RunMigrationsService.getInstance().invoke(RunMigrationsDto.fromPrimitives({}));

        this.#insertUsers();
        this.#insertTasks();
    }

    #insertUsers(): void {
        const users = [
            [UsersRawRepositoryMock.USER_UUID_OK, UsersRawRepositoryMock.USER_NAME_OK, UsersRawRepositoryMock.USER_EMAIL_OK, UsersRawRepositoryMock.USER_API_TOKEN_OK, ""],
            [UsersRawRepositoryMock.USER_UUID_TO_UPDATE, "luis.test", UsersRawRepositoryMock.USER_EMAIL_TO_UPDATE, UsersRawRepositoryMock.USER_API_TOKEN_TO_UPDATE, ""],
            [UsersRawRepositoryMock.USER_UUID_TO_DELETE, "eva.test", UsersRawRepositoryMock.USER_EMAIL_TO_DELETE, UsersRawRepositoryMock.USER_API_TOKEN_TO_DELETE, ""],
            [UsersRawRepositoryMock.USER_UUID_TO_REGENERATE, "rosa.test", UsersRawRepositoryMock.USER_EMAIL_TO_REGENERATE, UsersRawRepositoryMock.USER_API_TOKEN_TO_REGENERATE, ""],
            [UsersRawRepositoryMock.USER_UUID_SEARCH, "sara.test", UsersRawRepositoryMock.USER_EMAIL_SEARCH, UsersRawRepositoryMock.USER_API_TOKEN_SEARCH, ""],
            [UsersRawRepositoryMock.USER_UUID_TO_LOG_IN, UsersRawRepositoryMock.USER_NAME_TO_LOG_IN, UsersRawRepositoryMock.USER_EMAIL_TO_LOG_IN, UsersRawRepositoryMock.USER_API_TOKEN_TO_LOG_IN, UsersRawRepositoryMock.USER_PASSWORD_TO_LOG_IN],
        ];
        const sql = `
        -- insertUsers (fixtures)
        INSERT INTO \`users\`
            (user_uuid, user_name, user_email, password_hash, api_token_hash, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?)
        `;
        const passwordHasher = PasswordHasher.getInstance();
        const tokenGenerator = TokenGenerator.getInstance();
        for (const [userUuid, userName, userEmail, apiToken, password] of users) {
            const passwordHash = password ? passwordHasher.getPasswordHash(password) : null;
            this.execute(sql, [userUuid, userName, userEmail, passwordHash, tokenGenerator.getTokenHash(apiToken), NOW, NOW]);
        }
    }

    #insertTasks(): void {
        const tasks = [
            [TasksRawRepositoryMock.TASK_UUID_OK, TasksRawRepositoryMock.TASK_TITLE_OK],
            [TasksRawRepositoryMock.TASK_UUID_TO_UPDATE, "Llamar al banco"],
            [TasksRawRepositoryMock.TASK_UUID_TO_DELETE, "Tirar la basura"],
            [TasksRawRepositoryMock.TASK_UUID_TO_DELETE_GRAPHQL, "Regar las plantas"],
        ];
        const sql = `
        -- insertTasks (fixtures)
        INSERT INTO \`tasks\`
            (user_id, task_uuid, task_title, created_at, updated_at)
        VALUES
            ((SELECT id FROM \`users\` WHERE 1=1 AND user_uuid = ?), ?, ?, ?, ?)
        `;
        for (const [taskUuid, taskTitle] of tasks) {
            this.execute(sql, [UsersRawRepositoryMock.USER_UUID_OK, taskUuid, taskTitle, NOW, NOW]);
        }
        this.#insertSearchTasks();
    }

    /**
     * Las de la busqueda paginada: en este orden, asi que id desc = gamma, beta, alpha.
     */
    #insertSearchTasks(): void {
        const tasks = [
            [TasksRawRepositoryMock.SEARCH_TITLE_ALPHA, "pending", "work", TasksRawRepositoryMock.SEARCH_TARGET_DATE_ALPHA, "first"],
            [TasksRawRepositoryMock.SEARCH_TITLE_BETA, "done", "personal", TasksRawRepositoryMock.SEARCH_TARGET_DATE_BETA, "second"],
            [TasksRawRepositoryMock.SEARCH_TITLE_GAMMA, "pending", "other", null, TasksRawRepositoryMock.SEARCH_DESCRIPTION_GAMMA],
        ];
        const sql = `
        -- insertSearchTasks (fixtures)
        INSERT INTO \`tasks\`
            (user_id, task_uuid, task_title, task_status, task_type, target_date, task_description, created_at, updated_at)
        VALUES
            ((SELECT id FROM \`users\` WHERE 1=1 AND user_uuid = ?), ?, ?, ?, ?, ?, ?, ?, ?)
        `;
        tasks.forEach(([taskTitle, taskStatus, taskType, targetDate, taskDescription], i) => {
            this.execute(sql, [
                UsersRawRepositoryMock.USER_UUID_SEARCH, `eeeeeeee-eeee-4eee-8eee-${String(i).padStart(12, "0")}`,
                taskTitle, taskStatus, taskType, targetDate, taskDescription, NOW, NOW,
            ]);
        });
    }
}
