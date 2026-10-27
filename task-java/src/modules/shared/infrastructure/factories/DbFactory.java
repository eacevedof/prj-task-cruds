package modules.shared.infrastructure.factories;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.util.HashMap;
import java.util.Map;

import org.sqlite.SQLiteConfig;

import modules.shared.infrastructure.repositories.configuration.EnvironmentReaderRawRepository;

public final class DbFactory {

    private static final int BUSY_TIMEOUT_MS = 5000;

    // una conexion por hilo y fichero (en PHP, un PDO por repositorio y peticion): cada hilo de la API usa la suya y
    // SQLite ordena las escrituras de todas con busy_timeout
    private static final ThreadLocal<Map<String, Connection>> SQLITE_CONNECTIONS_BY_PATH = ThreadLocal.withInitial(HashMap::new);

    public static DbFactory getInstance() {
        return new DbFactory();
    }

    /**
     * La BD del fichero de APP_SQLITE_PATH. Si el fichero no existe, SQLite lo crea (lo normal en el primer
     * make migrate). La conexion lleva foreign_keys = ON (el ON DELETE CASCADE de tasks lo necesita) y espera
     * hasta BUSY_TIMEOUT_MS si otra conexion esta escribiendo, en vez de responder "database is locked".
     * @throws IllegalStateException
     */
    public Connection getSqlitePdoByEnvConfiguration() {
        String pathSqlite = EnvironmentReaderRawRepository.getInstance().getSqlitePath();
        if (pathSqlite.isEmpty()) {
            throw new IllegalStateException("missing env APP_SQLITE_PATH");
        }

        Map<String, Connection> sqliteConnectionsByPath = SQLITE_CONNECTIONS_BY_PATH.get();
        Connection sqliteConnection = sqliteConnectionsByPath.get(pathSqlite);
        if (sqliteConnection != null) {
            return sqliteConnection;
        }

        SQLiteConfig sqliteConfig = new SQLiteConfig();
        sqliteConfig.enforceForeignKeys(true);
        sqliteConfig.setBusyTimeout(BUSY_TIMEOUT_MS);
        try {
            Connection newSqliteConnection = DriverManager.getConnection("jdbc:sqlite:" + pathSqlite, sqliteConfig.toProperties());
            sqliteConnectionsByPath.put(pathSqlite, newSqliteConnection);
            return newSqliteConnection;
        }
        catch (SQLException e) {
            throw new IllegalStateException(e.getMessage(), e);
        }
    }

    /**
     * Cierra las conexiones abiertas del hilo (los tests, antes de borrar el fichero de su BD).
     */
    public void closeAll() {
        for (Connection sqliteConnection : SQLITE_CONNECTIONS_BY_PATH.get().values()) {
            try {
                sqliteConnection.close();
            }
            catch (SQLException e) {
                throw new IllegalStateException(e.getMessage(), e);
            }
        }
        SQLITE_CONNECTIONS_BY_PATH.get().clear();
    }
}
