package boot

import (
	"os"
	"path/filepath"
)

// BootPathsEnum rutas absolutas de la app. La raiz es la primera carpeta con go.mod subiendo desde el cwd
// (en el contenedor /app; en un test, el paquete que se prueba): el equivalente al dirname(__DIR__, 3) de PHP.
var BootPathsEnum = func() struct {
	PATH_ROOT       string
	PATH_PUBLIC     string
	PATH_SRC        string
	PATH_DISK_CACHE string
	PATH_LOGS       string
	PATH_CONSOLE    string
	PATH_MIGRATIONS string
} {
	pathRoot := getPathRoot()
	return struct {
		PATH_ROOT       string
		PATH_PUBLIC     string
		PATH_SRC        string
		PATH_DISK_CACHE string
		PATH_LOGS       string
		PATH_CONSOLE    string
		PATH_MIGRATIONS string
	}{
		PATH_ROOT:       pathRoot,
		PATH_PUBLIC:     filepath.Join(pathRoot, "public"),
		PATH_SRC:        filepath.Join(pathRoot, "src"),
		PATH_DISK_CACHE: filepath.Join(pathRoot, "storage", "cache"),
		PATH_LOGS:       filepath.Join(pathRoot, "storage", "logs"),
		PATH_CONSOLE:    filepath.Join(pathRoot, "console"),
		PATH_MIGRATIONS: filepath.Join(pathRoot, "database", "migrations"),
	}
}()

func getPathRoot() string {
	cwd, err := os.Getwd()
	if err != nil {
		return "."
	}
	for dir := cwd; ; dir = filepath.Dir(dir) {
		if _, err := os.Stat(filepath.Join(dir, "go.mod")); err == nil {
			return dir
		}
		if filepath.Dir(dir) == dir {
			return cwd
		}
	}
}
