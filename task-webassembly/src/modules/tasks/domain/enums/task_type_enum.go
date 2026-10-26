package enums

// TaskTypeEnum los mismos valores que la API (task-php TaskTypeEnum). La etiqueta la pone el Translator:
// tasks.type.<valor>
var TaskTypeEnum = struct {
	PERSONAL string
	WORK     string
	OTHER    string

	VALUES []string
}{
	PERSONAL: "personal",
	WORK:     "work",
	OTHER:    "other",

	VALUES: []string{"personal", "work", "other"},
}
