package repositories

import (
	"fmt"

	"task-go/src/modules/shared/application"
	sharedrepositories "task-go/src/modules/shared/infrastructure/repositories"
	"task-go/src/modules/users/domain/entities"
)

type UsersReaderSqliteRepository struct {
	sharedrepositories.AbstractSqliteRepository
}

func UsersReaderSqliteRepositoryGetInstance() *UsersReaderSqliteRepository {
	return &UsersReaderSqliteRepository{AbstractSqliteRepository: sharedrepositories.NewAbstractSqliteRepository()}
}

func (r *UsersReaderSqliteRepository) GetUserById(userId int) (*entities.UserEntity, error) {
	sql := fmt.Sprintf(`
        -- getUserById
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM `+"`users`"+`
        WHERE 1=1
        AND id = %d
        `, userId)
	return r.getFirstUserEntity(r.Query(sql))
}

func (r *UsersReaderSqliteRepository) GetUserByUserUuid(userUuid string) (*entities.UserEntity, error) {
	sql := `
        -- getUserByUserUuid
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM ` + "`users`" + `
        WHERE 1=1
        AND user_uuid = ?
        `
	return r.getFirstUserEntity(r.Query(sql, userUuid))
}

// GetUserByApiTokenHash autenticacion: el token llega en claro y aqui solo se compara su hash (api_token_hash es UNIQUE).
func (r *UsersReaderSqliteRepository) GetUserByApiTokenHash(apiTokenHash string) (*entities.UserEntity, error) {
	sql := `
        -- getUserByApiTokenHash
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM ` + "`users`" + `
        WHERE 1=1
        AND api_token_hash = ?
        `
	return r.getFirstUserEntity(r.Query(sql, apiTokenHash))
}

// GetUserByUserName login: user_name es unico sin distinguir mayusculas (indice uk_users_user_name COLLATE NOCASE).
func (r *UsersReaderSqliteRepository) GetUserByUserName(userName string) (*entities.UserEntity, error) {
	sql := `
        -- getUserByUserName
        SELECT
            id, user_uuid, user_name, user_email, created_at, updated_at
        FROM ` + "`users`" + `
        WHERE 1=1
        AND user_name = ? COLLATE NOCASE
        `
	return r.getFirstUserEntity(r.Query(sql, userName))
}

// GetPasswordHashByUserId fuera de UserEntity a proposito: el hash solo lo necesita el login y nunca debe acabar en
// una respuesta. "" = cuenta sin password (creada antes de 20261025101838-add-password-hash-to-users.sql).
func (r *UsersReaderSqliteRepository) GetPasswordHashByUserId(userId int) (string, error) {
	sql := fmt.Sprintf(`
        -- getPasswordHashByUserId
        SELECT
            password_hash
        FROM `+"`users`"+`
        WHERE 1=1
        AND id = %d
        `, userId)
	rows, err := r.Query(sql)
	if err != nil || len(rows) == 0 {
		return "", err
	}
	return application.ToString(rows[0]["password_hash"]), nil
}

// DoesUserNameExist unicidad del user_name en el alta (sin distinguir mayusculas, como el indice).
func (r *UsersReaderSqliteRepository) DoesUserNameExist(userName string) (bool, error) {
	sql := `
        -- doesUserNameExist
        SELECT
            COUNT(*) AS n_rows
        FROM ` + "`users`" + `
        WHERE 1=1
        AND user_name = ? COLLATE NOCASE
        `
	return r.getHasRows(r.Query(sql, userName))
}

// DoesUserNameExistInOtherUser unicidad del user_name en la modificacion: el del propio usuario no cuenta como
// repetido.
func (r *UsersReaderSqliteRepository) DoesUserNameExistInOtherUser(userEntity *entities.UserEntity) (bool, error) {
	sql := fmt.Sprintf(`
        -- doesUserNameExistInOtherUser
        SELECT
            COUNT(*) AS n_rows
        FROM `+"`users`"+`
        WHERE 1=1
        AND user_name = ? COLLATE NOCASE
        AND id != %d
        `, userEntity.GetId())
	return r.getHasRows(r.Query(sql, userEntity.GetUserName()))
}

// DoesUserEmailExist unicidad del email en el alta (sin distinguir mayusculas: la columna es COLLATE NOCASE).
func (r *UsersReaderSqliteRepository) DoesUserEmailExist(userEmail string) (bool, error) {
	sql := `
        -- doesUserEmailExist
        SELECT
            COUNT(*) AS n_rows
        FROM ` + "`users`" + `
        WHERE 1=1
        AND user_email = ?
        `
	return r.getHasRows(r.Query(sql, userEmail))
}

// DoesUserEmailExistInOtherUser unicidad del email en la modificacion: el email del propio usuario no cuenta como
// repetido.
func (r *UsersReaderSqliteRepository) DoesUserEmailExistInOtherUser(userEntity *entities.UserEntity) (bool, error) {
	sql := fmt.Sprintf(`
        -- doesUserEmailExistInOtherUser
        SELECT
            COUNT(*) AS n_rows
        FROM `+"`users`"+`
        WHERE 1=1
        AND user_email = ?
        AND id != %d
        `, userEntity.GetId())
	return r.getHasRows(r.Query(sql, userEntity.GetUserEmail()))
}

func (r *UsersReaderSqliteRepository) getFirstUserEntity(rows []map[string]any, err error) (*entities.UserEntity, error) {
	if err != nil || len(rows) == 0 {
		return nil, err
	}
	return entities.UserEntityFromPrimitives(rows[0]), nil
}

func (r *UsersReaderSqliteRepository) getHasRows(rows []map[string]any, err error) (bool, error) {
	if err != nil || len(rows) == 0 {
		return false, err
	}
	return application.ToInt(rows[0]["n_rows"]) > 0, nil
}
