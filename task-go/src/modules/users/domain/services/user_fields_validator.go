package services

import (
	"regexp"
	"strings"
	"unicode/utf8"

	"task-go/src/core/config"
	"task-go/src/modules/users/domain/enums"
)

const (
	// user_name es el identificador de login: sin espacios, unico sin distinguir mayusculas (indice en BD)
	USER_NAME_MIN_LENGTH = 3
	USER_NAME_MAX_LENGTH = 50

	USER_EMAIL_MAX_LENGTH = 150

	// bcrypt solo usa los primeros 72 bytes: mas largo daria por buena una password que no coincide entera
	PASSWORD_MIN_LENGTH = 8
	PASSWORD_MAX_BYTES  = 72
)

var (
	userNamePattern = regexp.MustCompile(`^[A-Za-z0-9._-]+$`)
	// el FILTER_VALIDATE_EMAIL de PHP: parte local de atomos separados por puntos y dominio con al menos un punto
	userEmailPattern = regexp.MustCompile(
		"^[A-Za-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\\.[A-Za-z0-9!#$%&'*+/=?^_`{|}~-]+)*" +
			`@(?:[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?$`,
	)
)

// UserFieldsValidator reglas de escritura de un usuario (alta y modificacion). Devuelve un error "campo: mensaje" por
// cada regla incumplida; vacio = valido.
type UserFieldsValidator struct{}

func UserFieldsValidatorGetInstance() *UserFieldsValidator {
	return &UserFieldsValidator{}
}

func (v *UserFieldsValidator) GetErrors(userName string, userEmail string) []string {
	var errors []string
	userNameLength := utf8.RuneCountInString(userName)
	switch {
	case userName == "":
		errors = append(errors, enums.UsersKeyEnum.USER_NAME+": "+config.Tr(enums.UsersMessageEnum.FIELD_IS_REQUIRED))
	case userNameLength < USER_NAME_MIN_LENGTH:
		errors = append(errors, enums.UsersKeyEnum.USER_NAME+": "+config.Tr(enums.UsersMessageEnum.MIN_LENGTH_IS_X, USER_NAME_MIN_LENGTH))
	case userNameLength > USER_NAME_MAX_LENGTH:
		errors = append(errors, enums.UsersKeyEnum.USER_NAME+": "+config.Tr(enums.UsersMessageEnum.MAX_LENGTH_IS_X, USER_NAME_MAX_LENGTH))
	case !userNamePattern.MatchString(userName):
		errors = append(errors, enums.UsersKeyEnum.USER_NAME+": "+config.Tr(enums.UsersMessageEnum.ONLY_LETTERS_DIGITS_DOT_UNDERSCORE_HYPHEN))
	}

	switch {
	case userEmail == "":
		errors = append(errors, enums.UsersKeyEnum.USER_EMAIL+": "+config.Tr(enums.UsersMessageEnum.FIELD_IS_REQUIRED))
	case utf8.RuneCountInString(userEmail) > USER_EMAIL_MAX_LENGTH:
		errors = append(errors, enums.UsersKeyEnum.USER_EMAIL+": "+config.Tr(enums.UsersMessageEnum.MAX_LENGTH_IS_X, USER_EMAIL_MAX_LENGTH))
	case !v.isValidEmail(userEmail):
		errors = append(errors, enums.UsersKeyEnum.USER_EMAIL+": "+config.Tr(enums.UsersMessageEnum.INVALID_EMAIL))
	}
	return errors
}

// GetPasswordErrors la password llega con trim (como todo string de un dto) tanto en el alta como en el login:
// coinciden.
func (v *UserFieldsValidator) GetPasswordErrors(password string) []string {
	if password == "" {
		return []string{enums.UsersKeyEnum.PASSWORD + ": " + config.Tr(enums.UsersMessageEnum.FIELD_IS_REQUIRED)}
	}
	if utf8.RuneCountInString(password) < PASSWORD_MIN_LENGTH {
		return []string{enums.UsersKeyEnum.PASSWORD + ": " + config.Tr(enums.UsersMessageEnum.MIN_LENGTH_IS_X, PASSWORD_MIN_LENGTH)}
	}
	if len(password) > PASSWORD_MAX_BYTES {
		return []string{enums.UsersKeyEnum.PASSWORD + ": " + config.Tr(enums.UsersMessageEnum.MAX_LENGTH_IS_X, PASSWORD_MAX_BYTES)}
	}
	return nil
}

func (v *UserFieldsValidator) isValidEmail(userEmail string) bool {
	localPart, _, _ := strings.Cut(userEmail, "@")
	return len(localPart) <= 64 && userEmailPattern.MatchString(userEmail)
}
