from Modules.Devops.Application.RunMigrations.RunMigrationsDto import RunMigrationsDto
from Modules.Devops.Application.RunMigrations.RunMigrationsService import RunMigrationsService
from Modules.Shared.Infrastructure.Components.Password.PasswordHasher import PasswordHasher
from Modules.Shared.Infrastructure.Components.Token.TokenGenerator import TokenGenerator
from Modules.Shared.Infrastructure.Repositories.AbstractSqliteRepository import AbstractSqliteRepository

from tests.Modules.Shared.Infrastructure.Repositories.TasksRawRepositoryMock import TasksRawRepositoryMock
from tests.Modules.Shared.Infrastructure.Repositories.UsersRawRepositoryMock import UsersRawRepositoryMock

NOW = "2026-01-01 00:00:00"


class FixturesRawRepositoryMock(AbstractSqliteRepository):
    """
    BD de tests desde cero: las migraciones reales de database/migrations + los fixtures de
    UsersRawRepositoryMock y TasksRawRepositoryMock. Lo lanza tests/bootstrap.py una vez por clase de test.
    """

    @staticmethod
    def getInstance() -> "FixturesRawRepositoryMock":
        return FixturesRawRepositoryMock()

    def createDbWithFixtures(self) -> None:
        RunMigrationsService.getInstance().invoke(RunMigrationsDto.fromPrimitives({}))

        self._insertUsers()
        self._insertTasks()

    def _insertUsers(self) -> None:
        users = [
            [UsersRawRepositoryMock.USER_UUID_OK, UsersRawRepositoryMock.USER_NAME_OK, UsersRawRepositoryMock.USER_EMAIL_OK, UsersRawRepositoryMock.USER_API_TOKEN_OK, ""],
            [UsersRawRepositoryMock.USER_UUID_TO_UPDATE, "luis.test", UsersRawRepositoryMock.USER_EMAIL_TO_UPDATE, UsersRawRepositoryMock.USER_API_TOKEN_TO_UPDATE, ""],
            [UsersRawRepositoryMock.USER_UUID_TO_DELETE, "eva.test", UsersRawRepositoryMock.USER_EMAIL_TO_DELETE, UsersRawRepositoryMock.USER_API_TOKEN_TO_DELETE, ""],
            [UsersRawRepositoryMock.USER_UUID_TO_REGENERATE, "rosa.test", UsersRawRepositoryMock.USER_EMAIL_TO_REGENERATE, UsersRawRepositoryMock.USER_API_TOKEN_TO_REGENERATE, ""],
            [UsersRawRepositoryMock.USER_UUID_SEARCH, "sara.test", UsersRawRepositoryMock.USER_EMAIL_SEARCH, UsersRawRepositoryMock.USER_API_TOKEN_SEARCH, ""],
            [UsersRawRepositoryMock.USER_UUID_TO_LOG_IN, UsersRawRepositoryMock.USER_NAME_TO_LOG_IN, UsersRawRepositoryMock.USER_EMAIL_TO_LOG_IN, UsersRawRepositoryMock.USER_API_TOKEN_TO_LOG_IN, UsersRawRepositoryMock.USER_PASSWORD_TO_LOG_IN],
        ]
        sql = """
        -- insertUsers (fixtures)
        INSERT INTO `users`
            (user_uuid, user_name, user_email, password_hash, api_token_hash, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?)
        """
        passwordHasher = PasswordHasher.getInstance()
        tokenGenerator = TokenGenerator.getInstance()
        for userUuid, userName, userEmail, apiToken, password in users:
            passwordHash = passwordHasher.getPasswordHash(password) if password else None
            self.execute(sql, [userUuid, userName, userEmail, passwordHash, tokenGenerator.getTokenHash(apiToken), NOW, NOW])

    def _insertTasks(self) -> None:
        tasks = [
            [TasksRawRepositoryMock.TASK_UUID_OK, TasksRawRepositoryMock.TASK_TITLE_OK],
            [TasksRawRepositoryMock.TASK_UUID_TO_UPDATE, "Llamar al banco"],
            [TasksRawRepositoryMock.TASK_UUID_TO_DELETE, "Tirar la basura"],
            [TasksRawRepositoryMock.TASK_UUID_TO_DELETE_GRAPHQL, "Regar las plantas"],
        ]
        sql = """
        -- insertTasks (fixtures)
        INSERT INTO `tasks`
            (user_id, task_uuid, task_title, created_at, updated_at)
        VALUES
            ((SELECT id FROM `users` WHERE 1=1 AND user_uuid = ?), ?, ?, ?, ?)
        """
        for taskUuid, taskTitle in tasks:
            self.execute(sql, [UsersRawRepositoryMock.USER_UUID_OK, taskUuid, taskTitle, NOW, NOW])
        self._insertSearchTasks()

    def _insertSearchTasks(self) -> None:
        """
        Las de la busqueda paginada: en este orden, asi que id desc = gamma, beta, alpha.
        """
        tasks = [
            [TasksRawRepositoryMock.SEARCH_TITLE_ALPHA, "pending", "work", TasksRawRepositoryMock.SEARCH_TARGET_DATE_ALPHA, "first"],
            [TasksRawRepositoryMock.SEARCH_TITLE_BETA, "done", "personal", TasksRawRepositoryMock.SEARCH_TARGET_DATE_BETA, "second"],
            [TasksRawRepositoryMock.SEARCH_TITLE_GAMMA, "pending", "other", None, TasksRawRepositoryMock.SEARCH_DESCRIPTION_GAMMA],
        ]
        sql = """
        -- insertSearchTasks (fixtures)
        INSERT INTO `tasks`
            (user_id, task_uuid, task_title, task_status, task_type, target_date, task_description, created_at, updated_at)
        VALUES
            ((SELECT id FROM `users` WHERE 1=1 AND user_uuid = ?), ?, ?, ?, ?, ?, ?, ?, ?)
        """
        for i, (taskTitle, taskStatus, taskType, targetDate, taskDescription) in enumerate(tasks):
            self.execute(sql, [
                UsersRawRepositoryMock.USER_UUID_SEARCH, f"eeeeeeee-eeee-4eee-8eee-{str(i).zfill(12)}",
                taskTitle, taskStatus, taskType, targetDate, taskDescription, NOW, NOW,
            ])
