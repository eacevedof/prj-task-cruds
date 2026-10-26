package boot

import "strings"

type SensitiveDataMasker struct{}

const sensitiveDataMask = "***"

var sensitiveKeyParts = []string{
	"password",
	"secret",
	"csrf",
	"token",
	"antibot",
}

func SensitiveDataMaskerGetInstance() *SensitiveDataMasker {
	return &SensitiveDataMasker{}
}

func (s *SensitiveDataMasker) GetMaskedArray(data map[string]any) map[string]any {
	masked := map[string]any{}
	for key, value := range data {
		if s.isSensitiveKey(key) {
			masked[key] = sensitiveDataMask
			continue
		}
		if subData, isMap := value.(map[string]any); isMap {
			masked[key] = s.GetMaskedArray(subData)
			continue
		}
		masked[key] = value
	}
	return masked
}

func (s *SensitiveDataMasker) isSensitiveKey(key string) bool {
	key = strings.ToLower(key)
	for _, sensitiveKeyPart := range sensitiveKeyParts {
		if strings.Contains(key, sensitiveKeyPart) {
			return true
		}
	}
	return false
}
