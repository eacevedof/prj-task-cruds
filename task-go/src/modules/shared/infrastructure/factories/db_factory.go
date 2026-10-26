package factories

import (
	"database/sql"
	"errors"
	"sync"

	_ "modernc.org/sqlite"

	"task-go/src/modules/shared/infrastructure/repositories/configuration"
)

// una conexion por fichero para todo el proceso (en PHP, un PDO por repositorio y peticion)
var (
	sqliteDbsByPath = map[string]*sql.DB{}
	sqliteDbsMutex  sync.Mutex
)

type DbFactory struct{}

func GetInstance() *DbFactory {
	return &DbFactory{}
}

// GetSqlitePdoByEnvConfiguration la BD del fichero de APP_SQLITE_PATH. Si el fichero no existe, SQLite lo crea (lo
// normal en el primer make migrate). Cada conexion lleva foreign_keys = ON (el ON DELETE CASCADE de tasks lo
// necesita). Una sola conexion abierta: SQLite admite un escritor a la vez y asi nunca responde "database is locked".
func (f *DbFactory) GetSqlitePdoByEnvConfiguration() (*sql.DB, error) {
	pathSqlite := configuration.GetInstance().GetSqlitePath()
	if pathSqlite == "" {
		return nil, errors.New("missing env APP_SQLITE_PATH")
	}

	sqliteDbsMutex.Lock()
	defer sqliteDbsMutex.Unlock()
	if sqliteDb, isOpen := sqliteDbsByPath[pathSqlite]; isOpen {
		return sqliteDb, nil
	}

	sqliteDb, err := sql.Open("sqlite", pathSqlite+"?_pragma=foreign_keys(1)&_pragma=busy_timeout(5000)")
	if err != nil {
		return nil, err
	}
	sqliteDb.SetMaxOpenConns(1)
	sqliteDbsByPath[pathSqlite] = sqliteDb
	return sqliteDb, nil
}

// CloseAll cierra las conexiones abiertas (los tests, antes de borrar el fichero de su BD).
func (f *DbFactory) CloseAll() {
	sqliteDbsMutex.Lock()
	defer sqliteDbsMutex.Unlock()
	for pathSqlite, sqliteDb := range sqliteDbsByPath {
		_ = sqliteDb.Close()
		delete(sqliteDbsByPath, pathSqlite)
	}
}
