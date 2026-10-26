// Package repositories fixtures de la BD de tests y lectura de las respuestas.
package repositories

import (
	"fmt"

	"task-go/src/modules/devops/application/runmigrations"
	"task-go/src/modules/shared/infrastructure/components/password"
	"task-go/src/modules/shared/infrastructure/components/token"
	sharedrepositories "task-go/src/modules/shared/infrastructure/repositories"
)

const fixturesNow = "2026-01-01 00:00:00"

// FixturesRawRepositoryMock BD de tests desde cero: las migraciones reales de database/migrations + los fixtures de
// UsersRawRepositoryMock y TasksRawRepositoryMock. Lo lanza tests.RunWithTestDb una vez por paquete.
type FixturesRawRepositoryMock struct {
	sharedrepositories.AbstractSqliteRepository
}

func FixturesRawRepositoryMockGetInstance() *FixturesRawRepositoryMock {
	return &FixturesRawRepositoryMock{AbstractSqliteRepository: sharedrepositories.NewAbstractSqliteRepository()}
}

func (r *FixturesRawRepositoryMock) CreateDbWithFixtures() error {
	if _, err := runmigrations.RunMigrationsServiceGetInstance().Invoke(
		runmigrations.RunMigrationsDtoFromPrimitives(map[string]any{}),
	); err != nil {
		return err
	}
	if err := r.insertUsers(); err != nil {
		return err
	}
	return r.insertTasks()
}

func (r *FixturesRawRepositoryMock) insertUsers() error {
	users := [][5]string{
		{UsersRawRepositoryMock.USER_UUID_OK, UsersRawRepositoryMock.USER_NAME_OK, UsersRawRepositoryMock.USER_EMAIL_OK, UsersRawRepositoryMock.USER_API_TOKEN_OK, ""},
		{UsersRawRepositoryMock.USER_UUID_TO_UPDATE, "luis.test", UsersRawRepositoryMock.USER_EMAIL_TO_UPDATE, UsersRawRepositoryMock.USER_API_TOKEN_TO_UPDATE, ""},
		{UsersRawRepositoryMock.USER_UUID_TO_DELETE, "eva.test", UsersRawRepositoryMock.USER_EMAIL_TO_DELETE, UsersRawRepositoryMock.USER_API_TOKEN_TO_DELETE, ""},
		{UsersRawRepositoryMock.USER_UUID_TO_REGENERATE, "rosa.test", UsersRawRepositoryMock.USER_EMAIL_TO_REGENERATE, UsersRawRepositoryMock.USER_API_TOKEN_TO_REGENERATE, ""},
		{UsersRawRepositoryMock.USER_UUID_SEARCH, "sara.test", UsersRawRepositoryMock.USER_EMAIL_SEARCH, UsersRawRepositoryMock.USER_API_TOKEN_SEARCH, ""},
		{UsersRawRepositoryMock.USER_UUID_TO_LOG_IN, UsersRawRepositoryMock.USER_NAME_TO_LOG_IN, UsersRawRepositoryMock.USER_EMAIL_TO_LOG_IN, UsersRawRepositoryMock.USER_API_TOKEN_TO_LOG_IN, UsersRawRepositoryMock.USER_PASSWORD_TO_LOG_IN},
	}
	sql := `
        -- insertUsers (fixtures)
        INSERT INTO ` + "`users`" + `
            (user_uuid, user_name, user_email, password_hash, api_token_hash, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?)
        `
	passwordHasher := password.GetInstance()
	tokenGenerator := token.GetInstance()
	for _, user := range users {
		userUuid, userName, userEmail, apiToken, userPassword := user[0], user[1], user[2], user[3], user[4]
		var passwordHash any
		if userPassword != "" {
			hash, err := passwordHasher.GetPasswordHash(userPassword)
			if err != nil {
				return err
			}
			passwordHash = hash
		}
		if _, err := r.Execute(sql, userUuid, userName, userEmail, passwordHash, tokenGenerator.GetTokenHash(apiToken), fixturesNow, fixturesNow); err != nil {
			return err
		}
	}
	return nil
}

func (r *FixturesRawRepositoryMock) insertTasks() error {
	tasks := [][2]string{
		{TasksRawRepositoryMock.TASK_UUID_OK, TasksRawRepositoryMock.TASK_TITLE_OK},
		{TasksRawRepositoryMock.TASK_UUID_TO_UPDATE, "Llamar al banco"},
		{TasksRawRepositoryMock.TASK_UUID_TO_DELETE, "Tirar la basura"},
		{TasksRawRepositoryMock.TASK_UUID_TO_DELETE_GRAPHQL, "Regar las plantas"},
	}
	sql := `
        -- insertTasks (fixtures)
        INSERT INTO ` + "`tasks`" + `
            (user_id, task_uuid, task_title, created_at, updated_at)
        VALUES
            ((SELECT id FROM ` + "`users`" + ` WHERE 1=1 AND user_uuid = ?), ?, ?, ?, ?)
        `
	for _, task := range tasks {
		if _, err := r.Execute(sql, UsersRawRepositoryMock.USER_UUID_OK, task[0], task[1], fixturesNow, fixturesNow); err != nil {
			return err
		}
	}
	return r.insertSearchTasks()
}

// insertSearchTasks las de la busqueda paginada: en este orden, asi que id desc = gamma, beta, alpha.
func (r *FixturesRawRepositoryMock) insertSearchTasks() error {
	tasks := [][5]any{
		{TasksRawRepositoryMock.SEARCH_TITLE_ALPHA, "pending", "work", TasksRawRepositoryMock.SEARCH_TARGET_DATE_ALPHA, "first"},
		{TasksRawRepositoryMock.SEARCH_TITLE_BETA, "done", "personal", TasksRawRepositoryMock.SEARCH_TARGET_DATE_BETA, "second"},
		{TasksRawRepositoryMock.SEARCH_TITLE_GAMMA, "pending", "other", nil, TasksRawRepositoryMock.SEARCH_DESCRIPTION_GAMMA},
	}
	sql := `
        -- insertSearchTasks (fixtures)
        INSERT INTO ` + "`tasks`" + `
            (user_id, task_uuid, task_title, task_status, task_type, target_date, task_description, created_at, updated_at)
        VALUES
            ((SELECT id FROM ` + "`users`" + ` WHERE 1=1 AND user_uuid = ?), ?, ?, ?, ?, ?, ?, ?, ?)
        `
	for i, task := range tasks {
		if _, err := r.Execute(sql,
			UsersRawRepositoryMock.USER_UUID_SEARCH, fmt.Sprintf("eeeeeeee-eeee-4eee-8eee-%012d", i),
			task[0], task[1], task[2], task[3], task[4], fixturesNow, fixturesNow,
		); err != nil {
			return err
		}
	}
	return nil
}
