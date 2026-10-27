package modules.shared.infrastructure.repositories;

import modules.devops.application.runmigrations.RunMigrationsDto;
import modules.devops.application.runmigrations.RunMigrationsService;
import modules.shared.application.Primitives;
import modules.shared.infrastructure.components.password.PasswordHasher;
import modules.shared.infrastructure.components.token.TokenGenerator;

/**
 * BD de tests desde cero: las migraciones reales de database/migrations + los fixtures de
 * UsersRawRepositoryMock y TasksRawRepositoryMock. Lo lanza testcase.Bootstrap una vez por clase de test.
 */
public final class FixturesRawRepositoryMock extends AbstractSqliteRepository {

    private static final String NOW = "2026-01-01 00:00:00";

    public static FixturesRawRepositoryMock getInstance() {
        return new FixturesRawRepositoryMock();
    }

    public void createDbWithFixtures() {
        RunMigrationsService.getInstance().invoke(RunMigrationsDto.fromPrimitives(Primitives.of()));

        insertUsers();
        insertTasks();
    }

    private void insertUsers() {
        String[][] users = {
            {UsersRawRepositoryMock.USER_UUID_OK, UsersRawRepositoryMock.USER_NAME_OK, UsersRawRepositoryMock.USER_EMAIL_OK, UsersRawRepositoryMock.USER_API_TOKEN_OK, ""},
            {UsersRawRepositoryMock.USER_UUID_TO_UPDATE, "luis.test", UsersRawRepositoryMock.USER_EMAIL_TO_UPDATE, UsersRawRepositoryMock.USER_API_TOKEN_TO_UPDATE, ""},
            {UsersRawRepositoryMock.USER_UUID_TO_DELETE, "eva.test", UsersRawRepositoryMock.USER_EMAIL_TO_DELETE, UsersRawRepositoryMock.USER_API_TOKEN_TO_DELETE, ""},
            {UsersRawRepositoryMock.USER_UUID_TO_REGENERATE, "rosa.test", UsersRawRepositoryMock.USER_EMAIL_TO_REGENERATE, UsersRawRepositoryMock.USER_API_TOKEN_TO_REGENERATE, ""},
            {UsersRawRepositoryMock.USER_UUID_SEARCH, "sara.test", UsersRawRepositoryMock.USER_EMAIL_SEARCH, UsersRawRepositoryMock.USER_API_TOKEN_SEARCH, ""},
            {UsersRawRepositoryMock.USER_UUID_TO_LOG_IN, UsersRawRepositoryMock.USER_NAME_TO_LOG_IN, UsersRawRepositoryMock.USER_EMAIL_TO_LOG_IN, UsersRawRepositoryMock.USER_API_TOKEN_TO_LOG_IN, UsersRawRepositoryMock.USER_PASSWORD_TO_LOG_IN},
        };
        String sql = """
            -- insertUsers (fixtures)
            INSERT INTO `users`
                (user_uuid, user_name, user_email, password_hash, api_token_hash, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?)
            """;
        PasswordHasher passwordHasher = PasswordHasher.getInstance();
        TokenGenerator tokenGenerator = TokenGenerator.getInstance();
        for (String[] user : users) {
            String password = user[4];
            String passwordHash = password.isEmpty() ? null : passwordHasher.getPasswordHash(password);
            execute(sql, user[0], user[1], user[2], passwordHash, tokenGenerator.getTokenHash(user[3]), NOW, NOW);
        }
    }

    private void insertTasks() {
        String[][] tasks = {
            {TasksRawRepositoryMock.TASK_UUID_OK, TasksRawRepositoryMock.TASK_TITLE_OK},
            {TasksRawRepositoryMock.TASK_UUID_TO_UPDATE, "Llamar al banco"},
            {TasksRawRepositoryMock.TASK_UUID_TO_DELETE, "Tirar la basura"},
            {TasksRawRepositoryMock.TASK_UUID_TO_DELETE_GRAPHQL, "Regar las plantas"},
        };
        String sql = """
            -- insertTasks (fixtures)
            INSERT INTO `tasks`
                (user_id, task_uuid, task_title, created_at, updated_at)
            VALUES
                ((SELECT id FROM `users` WHERE 1=1 AND user_uuid = ?), ?, ?, ?, ?)
            """;
        for (String[] task : tasks) {
            execute(sql, UsersRawRepositoryMock.USER_UUID_OK, task[0], task[1], NOW, NOW);
        }
        insertSearchTasks();
    }

    /**
     * Las de la busqueda paginada: en este orden, asi que id desc = gamma, beta, alpha.
     */
    private void insertSearchTasks() {
        String[][] tasks = {
            {TasksRawRepositoryMock.SEARCH_TITLE_ALPHA, "pending", "work", TasksRawRepositoryMock.SEARCH_TARGET_DATE_ALPHA, "first"},
            {TasksRawRepositoryMock.SEARCH_TITLE_BETA, "done", "personal", TasksRawRepositoryMock.SEARCH_TARGET_DATE_BETA, "second"},
            {TasksRawRepositoryMock.SEARCH_TITLE_GAMMA, "pending", "other", null, TasksRawRepositoryMock.SEARCH_DESCRIPTION_GAMMA},
        };
        String sql = """
            -- insertSearchTasks (fixtures)
            INSERT INTO `tasks`
                (user_id, task_uuid, task_title, task_status, task_type, target_date, task_description, created_at, updated_at)
            VALUES
                ((SELECT id FROM `users` WHERE 1=1 AND user_uuid = ?), ?, ?, ?, ?, ?, ?, ?, ?)
            """;
        for (int i = 0; i < tasks.length; i++) {
            String[] task = tasks[i];
            execute(sql,
                UsersRawRepositoryMock.USER_UUID_SEARCH, "eeeeeeee-eeee-4eee-8eee-" + String.format("%012d", i),
                task[0], task[1], task[2], task[3], task[4], NOW, NOW
            );
        }
    }
}
