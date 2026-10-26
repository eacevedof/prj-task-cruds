package repositories

import (
	"task-go/src/modules/shared/application"
	sharedrepositories "task-go/src/modules/shared/infrastructure/repositories"
)

type RunMigrationsReaderSqliteRepository struct {
	sharedrepositories.AbstractSqliteRepository
}

func RunMigrationsReaderSqliteRepositoryGetInstance() *RunMigrationsReaderSqliteRepository {
	return &RunMigrationsReaderSqliteRepository{AbstractSqliteRepository: sharedrepositories.NewAbstractSqliteRepository()}
}

func (r *RunMigrationsReaderSqliteRepository) DoesMigrationsTableExist(tableName string) (bool, error) {
	return r.DoesTableExist(tableName)
}

func (r *RunMigrationsReaderSqliteRepository) GetRanMigrationsFiles() ([]map[string]any, error) {
	sql := `
        -- getRanMigrationsFiles
        SELECT
            id, migration_file
        FROM ` + "`migrations`" + `
        WHERE 1=1
        ORDER BY migration_file
        `
	return r.Query(sql)
}

func (r *RunMigrationsReaderSqliteRepository) GetMaxBatch() (int, error) {
	sql := `
        -- getMaxBatch
        SELECT
            MAX(batch) AS max_batch
        FROM ` + "`migrations`" + `
        WHERE 1=1
        `
	rows, err := r.Query(sql)
	if err != nil || len(rows) == 0 {
		return 0, err
	}
	return application.ToInt(rows[0]["max_batch"]), nil
}
