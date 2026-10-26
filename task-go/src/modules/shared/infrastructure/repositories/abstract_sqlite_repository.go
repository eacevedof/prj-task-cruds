package repositories

import (
	"context"
	"database/sql"
	"fmt"
	"regexp"
	"strconv"
	"strings"

	"task-go/src/modules/shared/application"
	"task-go/src/modules/shared/infrastructure/factories"
	"task-go/src/modules/shared/infrastructure/traits"
)

var (
	passwordHashPattern = regexp.MustCompile(`^\$(2[aby]|argon2)`)
	placeholderPattern  = regexp.MustCompile(`\?`)
)

// sqlExecutor *sql.DB o *sql.Tx: lo mismo para una sentencia suelta que dentro de una transaccion.
type sqlExecutor interface {
	ExecContext(ctx context.Context, query string, args ...any) (sql.Result, error)
	QueryContext(ctx context.Context, query string, args ...any) (*sql.Rows, error)
}

// AbstractSqliteRepository base de los repositorios: query/execute con parametros enlazados y cada sentencia al
// log de SQL (storage/logs/YYYY-MM-DD-sql.sql) lista para copiar y lanzar.
type AbstractSqliteRepository struct {
	traits.LogTrait
	db           *sql.DB
	sqlExecutor  sqlExecutor
	lastInsertId int64
}

// NewAbstractSqliteRepository sin APP_SQLITE_PATH no hay app: el panic llega al recover de IndexMain/ConsoleMain
// (en PHP, la Exception de DbFactory que nadie captura).
func NewAbstractSqliteRepository() AbstractSqliteRepository {
	sqliteDb, err := factories.GetInstance().GetSqlitePdoByEnvConfiguration()
	if err != nil {
		panic(err)
	}
	return AbstractSqliteRepository{db: sqliteDb, sqlExecutor: sqliteDb}
}

// Query SELECT con parametros enlazados (?). Devuelve las filas como maps columna => valor.
func (r *AbstractSqliteRepository) Query(sqlQuery string, params ...any) ([]map[string]any, error) {
	r.LogSql(r.getSqlForLog(sqlQuery, params), "")
	rows, err := r.sqlExecutor.QueryContext(context.Background(), sqlQuery, params...)
	if err != nil {
		return nil, err
	}
	defer rows.Close()

	columns, err := rows.Columns()
	if err != nil {
		return nil, err
	}
	result := []map[string]any{}
	for rows.Next() {
		values := make([]any, len(columns))
		valuePointers := make([]any, len(columns))
		for i := range values {
			valuePointers[i] = &values[i]
		}
		if err := rows.Scan(valuePointers...); err != nil {
			return nil, err
		}
		row := map[string]any{}
		for i, column := range columns {
			row[column] = values[i]
		}
		result = append(result, row)
	}
	return result, rows.Err()
}

// Execute INSERT/UPDATE/DELETE con parametros enlazados. Devuelve las filas afectadas.
func (r *AbstractSqliteRepository) Execute(sqlQuery string, params ...any) (int, error) {
	r.LogSql(r.getSqlForLog(sqlQuery, params), "")
	result, err := r.sqlExecutor.ExecContext(context.Background(), sqlQuery, params...)
	if err != nil {
		return 0, err
	}
	r.lastInsertId, _ = result.LastInsertId()
	affected, err := result.RowsAffected()
	return int(affected), err
}

// ExecuteRaw varias sentencias de golpe (p.ej. un fichero .sql). Sin parametros: solo SQL de confianza.
func (r *AbstractSqliteRepository) ExecuteRaw(sqlQuery string) error {
	r.LogSql(sqlQuery, "")
	_, err := r.sqlExecutor.ExecContext(context.Background(), sqlQuery)
	return err
}

// GetLastInsertId el id del ultimo INSERT de este repositorio (el lastInsertId() de PDO).
func (r *AbstractSqliteRepository) GetLastInsertId() int {
	return int(r.lastInsertId)
}

// RunInTransaction todo lo que haga fn va en una transaccion: si devuelve error se deshace entera.
func (r *AbstractSqliteRepository) RunInTransaction(fn func() error) error {
	tx, err := r.db.Begin()
	if err != nil {
		return err
	}
	r.sqlExecutor = tx
	defer func() { r.sqlExecutor = r.db }()

	if err := fn(); err != nil {
		_ = tx.Rollback()
		return err
	}
	return tx.Commit()
}

func (r *AbstractSqliteRepository) DoesTableExist(tableName string) (bool, error) {
	sqlQuery := `
        -- doesTableExist
        SELECT
            COUNT(*) AS n_rows
        FROM ` + "`sqlite_master`" + `
        WHERE 1=1
        AND type = 'table'
        AND name = ?
        `
	rows, err := r.Query(sqlQuery, tableName)
	if err != nil || len(rows) == 0 {
		return false, err
	}
	return application.ToInt(rows[0]["n_rows"]) > 0, nil
}

// getSqlForLog SOLO para el log: la query con cada parametro en su sitio (strings entre comillas), para copiarla de
// sql.sql y lanzarla tal cual. Nunca se ejecuta: la real va siempre con parametros enlazados.
// Asume que no hay "?" dentro de literales del SQL (no los hay en los repositorios).
// Los hashes de password (bcrypt $2y$..., argon2 $argon2...) salen como '***': no deben quedar en sql.sql.
func (r *AbstractSqliteRepository) getSqlForLog(sqlQuery string, params []any) string {
	if len(params) == 0 {
		return sqlQuery
	}

	position := 0
	return placeholderPattern.ReplaceAllStringFunc(sqlQuery, func(placeholder string) string {
		if position >= len(params) {
			return placeholder
		}
		param := params[position]
		position++
		return getParamForLog(param)
	})
}

func getParamForLog(param any) string {
	switch typedParam := param.(type) {
	case nil:
		return "NULL"
	case bool:
		if typedParam {
			return "1"
		}
		return "0"
	case int:
		return strconv.Itoa(typedParam)
	case int64:
		return strconv.FormatInt(typedParam, 10)
	case float64:
		return strconv.FormatFloat(typedParam, 'f', -1, 64)
	}
	text := fmt.Sprint(param)
	if passwordHashPattern.MatchString(text) {
		return "'***'"
	}
	return "'" + strings.ReplaceAll(text, "'", "''") + "'"
}
