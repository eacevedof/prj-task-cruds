package uuid

import (
	"crypto/rand"
	"encoding/hex"
	"fmt"
	"regexp"
	"sync"
	"time"
)

var uuidV4Pattern = regexp.MustCompile(`^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$`)

// el ultimo uniqid dado: dos llamadas en el mismo microsegundo no pueden repetirlo
var (
	lastUniqueIdMicros int64
	uniqueIdMutex      sync.Mutex
)

// UuidGenerator identificadores publicos: UUID v4 (RFC 4122) y el uniqid() de PHP con prefijo ("user-", "task-"),
// para que todas las replicas generen y validen lo mismo.
type UuidGenerator struct{}

func GetInstance() *UuidGenerator {
	return &UuidGenerator{}
}

func (u *UuidGenerator) GetUuidV4() string {
	randomBytes := make([]byte, 16)
	_, _ = rand.Read(randomBytes)
	randomBytes[6] = (randomBytes[6] & 0x0f) | 0x40
	randomBytes[8] = (randomBytes[8] & 0x3f) | 0x80
	hexBytes := hex.EncodeToString(randomBytes)
	return fmt.Sprintf("%s-%s-%s-%s-%s", hexBytes[0:8], hexBytes[8:12], hexBytes[12:16], hexBytes[16:20], hexBytes[20:32])
}

// GetUniqueIdWithPrefix prefix + uniqid(): 8 hex de los segundos + 5 hex de los microsegundos (13 caracteres).
func (u *UuidGenerator) GetUniqueIdWithPrefix(prefix string) string {
	uniqueIdMutex.Lock()
	defer uniqueIdMutex.Unlock()

	micros := time.Now().UnixMicro()
	if micros <= lastUniqueIdMicros {
		micros = lastUniqueIdMicros + 1
	}
	lastUniqueIdMicros = micros
	return fmt.Sprintf("%s%08x%05x", prefix, micros/1_000_000, micros%1_000_000)
}

func (u *UuidGenerator) IsUuidV4(uuid string) bool {
	return uuidV4Pattern.MatchString(uuid)
}
