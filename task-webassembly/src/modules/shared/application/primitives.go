package application

import (
	"strconv"
	"strings"
)

// Los dto se construyen desde un map de primitivos (el objeto plano de task-js: lo que llega en el evento o lo que
// devuelve la API ya decodificado). Estas funciones hacen los casts de JS: String(x ?? "").trim(), Number(x)... sobre
// un valor que puede faltar, ser nil o venir de otro tipo (los numeros del JSON llegan como float64).

// GetTrimmedString String(primitives[key] ?? "").trim()
func GetTrimmedString(primitives map[string]any, key string) string {
	return strings.TrimSpace(ToString(primitives[key]))
}

// GetInt Number(primitives[key] ?? 0)
func GetInt(primitives map[string]any, key string) int {
	return ToInt(primitives[key])
}

// GetIntOrDefault Number(primitives[key]) || defaultValue: 0, nil o lo que no es numero cuentan como ausentes.
func GetIntOrDefault(primitives map[string]any, key string, defaultValue int) int {
	if value := ToInt(primitives[key]); value != 0 {
		return value
	}
	return defaultValue
}

// ToString String(value ?? "")
func ToString(value any) string {
	switch typedValue := value.(type) {
	case nil:
		return ""
	case string:
		return typedValue
	case float64:
		return strconv.FormatFloat(typedValue, 'f', -1, 64)
	case int:
		return strconv.Itoa(typedValue)
	case bool:
		return strconv.FormatBool(typedValue)
	}
	return ""
}

// ToInt Number(value): lo que no es numero, 0.
func ToInt(value any) int {
	switch typedValue := value.(type) {
	case float64:
		return int(typedValue)
	case int:
		return typedValue
	case string:
		number, err := strconv.ParseFloat(strings.TrimSpace(typedValue), 64)
		if err != nil {
			return 0
		}
		return int(number)
	}
	return 0
}

// ToMap value ?? {}: un objeto plano de la API (o vacio si no lo es).
func ToMap(value any) map[string]any {
	if typedValue, ok := value.(map[string]any); ok {
		return typedValue
	}
	return map[string]any{}
}

// ToMaps Array.isArray(value) ? value : []: una lista de objetos planos de la API.
func ToMaps(value any) []map[string]any {
	items, ok := value.([]any)
	if !ok {
		return []map[string]any{}
	}
	maps := make([]map[string]any, 0, len(items))
	for _, item := range items {
		maps = append(maps, ToMap(item))
	}
	return maps
}
