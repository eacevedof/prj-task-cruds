package enums

// LanguageCodeEnum idiomas que entiende la API (subetiqueta primaria de Accept-Language, ISO 639-1).
var LanguageCodeEnum = struct {
	ES     string
	EN     string
	VALUES []string
}{
	ES:     "es",
	EN:     "en",
	VALUES: []string{"es", "en"},
}
