package application

import (
	"fmt"
	"maps"
	"math"
	"strconv"
	"strings"
)

// Los dto se construyen desde un map de primitivos (el array $primitives de task-php) para que el controller pueda
// juntar metadata + usuario autenticado + campos de la peticion en un solo map. Estas funciones hacen los casts de
// PHP: (string) y (int) sobre un valor que puede faltar, ser nil o venir de otro tipo.

// GetTrimmedString trim((string) ($primitives[$key] ?? ""))
func GetTrimmedString(primitives map[string]any, key string) string {
	return strings.TrimSpace(ToString(primitives[key]))
}

// GetInt (int) ($primitives[$key] ?? 0)
func GetInt(primitives map[string]any, key string) int {
	return ToInt(primitives[key])
}

// GetIntOrDefault (int) ($primitives[$key] ?? $default): nil cuenta como ausente.
func GetIntOrDefault(primitives map[string]any, key string, defaultValue int) int {
	value, isSet := primitives[key]
	if !isSet || value == nil {
		return defaultValue
	}
	return ToInt(value)
}

// MergePrimitives [...$a, ...$b]: con claves de texto, la ultima gana.
func MergePrimitives(primitivesList ...map[string]any) map[string]any {
	merged := map[string]any{}
	for _, primitives := range primitivesList {
		maps.Copy(merged, primitives)
	}
	return merged
}

// ToString (string) $value de PHP: true "1", false/nil "", 1.0 "1".
func ToString(value any) string {
	switch typedValue := value.(type) {
	case nil:
		return ""
	case string:
		return typedValue
	case bool:
		if typedValue {
			return "1"
		}
		return ""
	case int:
		return strconv.Itoa(typedValue)
	case int32:
		return strconv.FormatInt(int64(typedValue), 10)
	case int64:
		return strconv.FormatInt(typedValue, 10)
	case float64:
		return strconv.FormatFloat(typedValue, 'f', -1, 64)
	case []byte:
		return string(typedValue)
	case *string:
		if typedValue == nil {
			return ""
		}
		return *typedValue
	default:
		return fmt.Sprint(typedValue)
	}
}

// ToInt (int) $value de PHP: "12abc" 12, "abc" 0, 1.9 1, true 1.
func ToInt(value any) int {
	switch typedValue := value.(type) {
	case nil:
		return 0
	case int:
		return typedValue
	case int32:
		return int(typedValue)
	case int64:
		return int(typedValue)
	case float64:
		return int(math.Trunc(typedValue))
	case bool:
		if typedValue {
			return 1
		}
		return 0
	case *int32:
		if typedValue == nil {
			return 0
		}
		return int(*typedValue)
	default:
		return getLeadingInt(ToString(typedValue))
	}
}

func getLeadingInt(text string) int {
	text = strings.TrimSpace(text)
	end := 0
	for end < len(text) && (text[end] >= '0' && text[end] <= '9' || end == 0 && (text[end] == '-' || text[end] == '+')) {
		end++
	}
	number, err := strconv.Atoi(text[:end])
	if err != nil {
		return 0
	}
	return number
}
