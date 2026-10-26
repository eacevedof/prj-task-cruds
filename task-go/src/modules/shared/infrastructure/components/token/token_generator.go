package token

import (
	"crypto/rand"
	"crypto/sha256"
	"encoding/hex"
)

const tokenBytes = 32

// TokenGenerator tokens de API opacos: 32 bytes del CSPRNG en hex (256 bits). Con esa entropia basta un SHA-256 para
// guardarlos (no hace falta un hash lento tipo bcrypt, que es para contraseñas elegidas por humanos).
type TokenGenerator struct{}

func GetInstance() *TokenGenerator {
	return &TokenGenerator{}
}

func (t *TokenGenerator) GetNewToken() string {
	randomBytes := make([]byte, tokenBytes)
	_, _ = rand.Read(randomBytes)
	return hex.EncodeToString(randomBytes)
}

func (t *TokenGenerator) GetTokenHash(token string) string {
	tokenHash := sha256.Sum256([]byte(token))
	return hex.EncodeToString(tokenHash[:])
}
