package repositories

import (
	"fmt"
	"os"
	"strings"

	sharedrepositories "task-go/src/modules/shared/infrastructure/repositories"
)

type RunMigrationsWriterSqliteRepository struct {
	sharedrepositories.AbstractSqliteRepository
}

func RunMigrationsWriterSqliteRepositoryGetInstance() *RunMigrationsWriterSqliteRepository {
	return &RunMigrationsWriterSqliteRepository{AbstractSqliteRepository: sharedrepositories.NewAbstractSqliteRepository()}
}

// RunSqlFile el driver de SQLite ejecuta varias sentencias en un solo Exec: el fichero va entero, sin trocearlo
// por ";".
func (r *RunMigrationsWriterSqliteRepository) RunSqlFile(pathSqlFile string) error {
	sql, err := r.getFileContentSql(pathSqlFile)
	if err != nil || sql == "" {
		return err
	}
	return r.ExecuteRaw(sql)
}

// RunMigrationFileAndSave fichero + registro en migrations en la MISMA transaccion: si el SQL falla no queda aplicado
// a medias ni marcado como ejecutado. Ojo: en SQLite el DDL tambien es transaccional.
func (r *RunMigrationsWriterSqliteRepository) RunMigrationFileAndSave(pathSqlFile string, migrationFile string, batch int) error {
	return r.RunInTransaction(func() error {
		if err := r.RunSqlFile(pathSqlFile); err != nil {
			return err
		}
		return r.saveMigration(migrationFile, batch)
	})
}

func (r *RunMigrationsWriterSqliteRepository) saveMigration(migrationFile string, batch int) error {
	sql := fmt.Sprintf(`
        -- saveMigration
        INSERT INTO `+"`migrations`"+` (migration_file, batch)
        VALUES (?, %d)
        `, batch)
	_, err := r.Execute(sql, migrationFile)
	return err
}

func (r *RunMigrationsWriterSqliteRepository) getFileContentSql(pathSqlFile string) (string, error) {
	content, err := os.ReadFile(pathSqlFile)
	if err != nil {
		return "", err
	}
	return strings.TrimSpace(string(content)), nil
}
