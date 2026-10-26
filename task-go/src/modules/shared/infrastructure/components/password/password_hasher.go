package password

import (
	"strings"

	"golang.org/x/crypto/bcrypt"
)

// coste del PASSWORD_DEFAULT de PHP 8.4+ (bcrypt 12): un hash de task-php y uno de task-go se verifican entre si
const passwordHashCost = 12

// Hash de 32 bytes aleatorios ya descartados (nadie conoce la contraseña): se verifica contra el cuando el
// usuario no existe o no tiene contraseña, para que la respuesta tarde lo mismo y no delate que user_name existen.
const dummyHash = "$2y$12$kUEK9r0sqApY2hnb2ZtJIe4JTyxNGppqF0iNfU7ThCu6i/.FMhrHi"

// PasswordHasher contraseñas elegidas por humanos: hash lento con sal (bcrypt), al contrario que los api_token
// (TokenGenerator), que son aleatorios y les basta un SHA-256.
type PasswordHasher struct{}

func GetInstance() *PasswordHasher {
	return &PasswordHasher{}
}

// GetPasswordHash la password ya viene validada (8-72 bytes): bcrypt no admite mas de 72.
func (p *PasswordHasher) GetPasswordHash(password string) (string, error) {
	passwordHash, err := bcrypt.GenerateFromPassword([]byte(password), passwordHashCost)
	if err != nil {
		return "", err
	}
	return string(passwordHash), nil
}

// IsPasswordValid passwordHash "" = sin contraseña guardada: compara contra el hash ficticio y devuelve false.
func (p *PasswordHasher) IsPasswordValid(password string, passwordHash string) bool {
	if passwordHash == "" {
		_ = bcrypt.CompareHashAndPassword([]byte(dummyHash), []byte(password))
		return false
	}
	return bcrypt.CompareHashAndPassword([]byte(passwordHash), []byte(password)) == nil
}

// DoesPasswordHashNeedRehash true si el hash no es bcrypt o se hizo con otro coste: se rehace en el siguiente
// login correcto. $2y$ (PHP) y $2a$ (Go) son el mismo bcrypt: no se rehace por el prefijo.
func (p *PasswordHasher) DoesPasswordHashNeedRehash(passwordHash string) bool {
	if !strings.HasPrefix(passwordHash, "$2") {
		return true
	}
	cost, err := bcrypt.Cost([]byte(passwordHash))
	return err != nil || cost != passwordHashCost
}
