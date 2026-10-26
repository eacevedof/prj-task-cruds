package entities

import (
	"task-go/src/modules/shared/application"
	"task-go/src/modules/users/domain/enums"
)

const USER_UUID_PREFIX = "user-"

// UserPrimitives representacion publica (API): el id interno no sale, el identificador es user_uuid. Los hashes
// nunca. Struct y no map: el JSON sale con las claves en este orden, como en task-php.
type UserPrimitives struct {
	UserUuid  string `json:"user_uuid"`
	UserName  string `json:"user_name"`
	UserEmail string `json:"user_email"`
	CreatedAt string `json:"created_at"`
	UpdatedAt string `json:"updated_at"`
}

// UserWithApiTokenPrimitives el usuario + su api_token en claro: solo en el alta y en el login.
type UserWithApiTokenPrimitives struct {
	UserPrimitives
	ApiToken string `json:"api_token"`
}

type UserEntity struct {
	id           int
	userUuid     string
	userName     string
	userEmail    string
	passwordHash string
	apiTokenHash string
	createdAt    string
	updatedAt    string
}

// UserEntityFromPrimitives desde una fila de la tabla users (columnas en snake_case: contrato con la BD). Las
// lecturas no traen password_hash ni api_token_hash: solo los informan las escrituras.
func UserEntityFromPrimitives(primitives map[string]any) *UserEntity {
	return &UserEntity{
		id:           application.GetInt(primitives, enums.UsersKeyEnum.ID),
		userUuid:     application.GetTrimmedString(primitives, enums.UsersKeyEnum.USER_UUID),
		userName:     application.GetTrimmedString(primitives, enums.UsersKeyEnum.USER_NAME),
		userEmail:    application.GetTrimmedString(primitives, enums.UsersKeyEnum.USER_EMAIL),
		passwordHash: application.GetTrimmedString(primitives, enums.UsersKeyEnum.PASSWORD_HASH),
		apiTokenHash: application.GetTrimmedString(primitives, enums.UsersKeyEnum.API_TOKEN_HASH),
		createdAt:    application.GetTrimmedString(primitives, enums.UsersKeyEnum.CREATED_AT),
		updatedAt:    application.GetTrimmedString(primitives, enums.UsersKeyEnum.UPDATED_AT),
	}
}

func (e *UserEntity) ToPrimitives() UserPrimitives {
	return UserPrimitives{
		UserUuid:  e.userUuid,
		UserName:  e.userName,
		UserEmail: e.userEmail,
		CreatedAt: e.createdAt,
		UpdatedAt: e.updatedAt,
	}
}

// ToPrimitivesOrEmpty el ?->toPrimitives() ?? [] de task-php: una lectura que no encuentra nada da el usuario vacio.
func ToPrimitivesOrEmpty(userEntity *UserEntity) UserPrimitives {
	if userEntity == nil {
		return UserPrimitives{}
	}
	return userEntity.ToPrimitives()
}

func (e *UserEntity) GetId() int {
	return e.id
}

func (e *UserEntity) GetUserUuid() string {
	return e.userUuid
}

func (e *UserEntity) GetUserName() string {
	return e.userName
}

func (e *UserEntity) GetUserEmail() string {
	return e.userEmail
}

func (e *UserEntity) GetPasswordHash() string {
	return e.passwordHash
}

func (e *UserEntity) GetApiTokenHash() string {
	return e.apiTokenHash
}

func (e *UserEntity) GetCreatedAt() string {
	return e.createdAt
}

func (e *UserEntity) GetUpdatedAt() string {
	return e.updatedAt
}
