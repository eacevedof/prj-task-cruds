package repositories

import (
	"fmt"

	sharedrepositories "task-go/src/modules/shared/infrastructure/repositories"
	"task-go/src/modules/users/domain/entities"
)

type UsersWriterSqliteRepository struct {
	sharedrepositories.AbstractSqliteRepository
}

func UsersWriterSqliteRepositoryGetInstance() *UsersWriterSqliteRepository {
	return &UsersWriterSqliteRepository{AbstractSqliteRepository: sharedrepositories.NewAbstractSqliteRepository()}
}

// InsertUser en el alta updated_at = created_at.
func (r *UsersWriterSqliteRepository) InsertUser(userEntity *entities.UserEntity) (int, error) {
	sql := `
        -- insertUser
        INSERT INTO ` + "`users`" + `
            (user_uuid, user_name, user_email, password_hash, api_token_hash, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?)
        `
	_, err := r.Execute(sql,
		userEntity.GetUserUuid(),
		userEntity.GetUserName(),
		userEntity.GetUserEmail(),
		userEntity.GetPasswordHash(),
		userEntity.GetApiTokenHash(),
		userEntity.GetCreatedAt(),
		userEntity.GetCreatedAt(),
	)
	if err != nil {
		return 0, err
	}
	return r.GetLastInsertId(), nil
}

func (r *UsersWriterSqliteRepository) UpdateUserById(userEntity *entities.UserEntity) (int, error) {
	sql := fmt.Sprintf(`
        -- updateUserById
        UPDATE `+"`users`"+`
        SET user_name = ?, user_email = ?, updated_at = ?
        WHERE 1=1
        AND id = %d
        `, userEntity.GetId())
	return r.Execute(sql, userEntity.GetUserName(), userEntity.GetUserEmail(), userEntity.GetUpdatedAt())
}

// UpdateApiTokenHashById el token anterior deja de valer en el acto: solo existe el hash nuevo.
func (r *UsersWriterSqliteRepository) UpdateApiTokenHashById(userEntity *entities.UserEntity) (int, error) {
	sql := fmt.Sprintf(`
        -- updateApiTokenHashById
        UPDATE `+"`users`"+`
        SET api_token_hash = ?, updated_at = ?
        WHERE 1=1
        AND id = %d
        `, userEntity.GetId())
	return r.Execute(sql, userEntity.GetApiTokenHash(), userEntity.GetUpdatedAt())
}

// UpdatePasswordHashById rehash en el login cuando cambia el algoritmo o el coste del hash.
func (r *UsersWriterSqliteRepository) UpdatePasswordHashById(userEntity *entities.UserEntity) (int, error) {
	sql := fmt.Sprintf(`
        -- updatePasswordHashById
        UPDATE `+"`users`"+`
        SET password_hash = ?, updated_at = ?
        WHERE 1=1
        AND id = %d
        `, userEntity.GetId())
	return r.Execute(sql, userEntity.GetPasswordHash(), userEntity.GetUpdatedAt())
}

// DeleteUserById borra tambien sus tasks (FK ON DELETE CASCADE).
func (r *UsersWriterSqliteRepository) DeleteUserById(userEntity *entities.UserEntity) (int, error) {
	sql := fmt.Sprintf(`
        -- deleteUserById
        DELETE FROM `+"`users`"+`
        WHERE 1=1
        AND id = %d
        `, userEntity.GetId())
	return r.Execute(sql)
}
