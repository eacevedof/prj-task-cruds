// Package tests arranque y aserciones comunes de los tests (el tests/bootstrap.php y el AbstractTestCase de task-php).
package tests

import (
	"fmt"
	"os"
	"path/filepath"
	"testing"

	"task-go/src/core/boot"
	"task-go/src/core/config"
	"task-go/src/modules/shared/infrastructure/factories"
	"task-go/tests/modules/shared/infrastructure/repositories"
)

// RunWithTestDb el TestMain de cada paquete: mismo arranque que la API pero contra una BD SQLite PROPIA, que se
// borra y se recrea (migraciones + fixtures) en cada ejecucion. La BD de desarrollo no se toca.
// Una BD por paquete (testDbName): go test ./... ejecuta los paquetes en paralelo.
func RunWithTestDb(m *testing.M, testDbName string) int {
	pathSqliteTest := filepath.Join(boot.BootPathsEnum.PATH_ROOT, "database", "task_cruds_test-"+testDbName+".sqlite")
	if err := loadTestEnv(pathSqliteTest); err != nil {
		fmt.Fprintln(os.Stderr, err)
		return 1
	}

	_ = os.Remove(pathSqliteTest)
	if err := repositories.FixturesRawRepositoryMockGetInstance().CreateDbWithFixtures(); err != nil {
		fmt.Fprintln(os.Stderr, "fixtures:", err)
		return 1
	}
	defer factories.GetInstance().CloseAll()
	return m.Run()
}

// RunWithoutDb el TestMain de los paquetes que no tocan la BD.
func RunWithoutDb(m *testing.M) int {
	if err := loadTestEnv(filepath.Join(boot.BootPathsEnum.PATH_ROOT, "database", "task_cruds_test-nodb.sqlite")); err != nil {
		fmt.Fprintln(os.Stderr, err)
		return 1
	}
	return m.Run()
}

// loadTestEnv la variable REAL gana sobre el .env (AppbootLoadenv), asi que esta ruta manda.
// APP_EMAIL_TO vacio: los tests que provocan un 500 no mandan emails de alerta.
func loadTestEnv(pathSqliteTest string) error {
	for key, value := range map[string]string{
		"APP_SQLITE_PATH": pathSqliteTest,
		"APP_ENV":         "test",
		"APP_EMAIL_TO":    "",
		"APP_DOMAIN":      "localhost",
	} {
		if err := os.Setenv(key, value); err != nil {
			return err
		}
	}
	return config.AppbootLoadenv()
}
