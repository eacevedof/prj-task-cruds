package boot

// ENV catalogo de entornos. El COMPORTAMIENTO (getEnvironment/isLocal/isProduction/isDebug/color) vive en
// EnvironmentReaderRawRepository (shared/infrastructure/repositories/configuration).
var ENV = struct {
	LOCAL      string
	DEVELOP    string
	TEST       string
	PRODUCTION string
	COLORS     map[string]string
}{
	LOCAL:      "local",
	DEVELOP:    "develop",
	TEST:       "test",
	PRODUCTION: "production",
	COLORS: map[string]string{
		"local":      "#fc0ac3", //rosa
		"develop":    "#0174ee", //azul
		"test":       "#2f8a01", //verde
		"production": "",
	},
}
