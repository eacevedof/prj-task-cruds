package modules.shared.infrastructure.repositories;

import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.ResultSetMetaData;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import modules.shared.application.Primitives;
import modules.shared.infrastructure.factories.DbFactory;
import modules.shared.infrastructure.traits.LogTrait;

/**
 * Las sentencias van siempre con parametros enlazados (?). Un SQLException (de JDBC, checked) sube como
 * IllegalStateException: un repositorio no captura, el fallo llega al catch del controller o del command.
 */
public abstract class AbstractSqliteRepository {

    private static final Pattern PASSWORD_HASH_PATTERN = Pattern.compile("^\\$(2[aby]|argon2)");

    protected final Connection pdo;
    protected final LogTrait logTrait;

    protected AbstractSqliteRepository() {
        this.pdo = DbFactory.getInstance().getSqlitePdoByEnvConfiguration();
        this.logTrait = LogTrait.getInstance();
    }

    /**
     * SELECT con parametros enlazados (?). Devuelve las filas como mapas (columna => valor).
     */
    protected List<Map<String, Object>> query(String sql, Object... params) {
        logTrait.logSql(getSqlForLog(sql, params));
        try (PreparedStatement preparedStatement = getPreparedStatement(sql, params);
             ResultSet resultSet = preparedStatement.executeQuery()) {
            ResultSetMetaData resultSetMetaData = resultSet.getMetaData();
            List<Map<String, Object>> rows = new ArrayList<>();
            while (resultSet.next()) {
                Map<String, Object> row = new LinkedHashMap<>();
                for (int i = 1; i <= resultSetMetaData.getColumnCount(); i++) {
                    row.put(resultSetMetaData.getColumnLabel(i), resultSet.getObject(i));
                }
                rows.add(row);
            }
            return rows;
        }
        catch (SQLException e) {
            throw new IllegalStateException(e.getMessage(), e);
        }
    }

    /**
     * INSERT/UPDATE/DELETE con parametros enlazados. Devuelve las filas afectadas.
     */
    protected int execute(String sql, Object... params) {
        logTrait.logSql(getSqlForLog(sql, params));
        try (PreparedStatement preparedStatement = getPreparedStatement(sql, params)) {
            return preparedStatement.executeUpdate();
        }
        catch (SQLException e) {
            throw new IllegalStateException(e.getMessage(), e);
        }
    }

    /**
     * Varias sentencias de golpe (p.ej. un fichero .sql). Sin parametros: solo SQL de confianza.
     * JDBC ejecuta una sentencia cada vez: se trocea por ";" (fuera de comillas y comentarios).
     */
    protected void executeRaw(String sql) {
        logTrait.logSql(sql);
        try (Statement statement = pdo.createStatement()) {
            for (String singleSql : getSingleStatements(sql)) {
                statement.execute(singleSql);
            }
        }
        catch (SQLException e) {
            throw new IllegalStateException(e.getMessage(), e);
        }
    }

    /**
     * El id de la ultima fila insertada en esta conexion (el lastInsertId() de PDO).
     */
    protected int lastInsertId() {
        List<Map<String, Object>> rows = query("SELECT last_insert_rowid() AS last_insert_id");
        return Primitives.getInt(rows.getFirst().get("last_insert_id"));
    }

    protected boolean doesTableExist(String tableName) {
        String sql = """
            -- doesTableExist
            SELECT
                COUNT(*) AS n_rows
            FROM `sqlite_master`
            WHERE 1=1
            AND type = 'table'
            AND name = ?
            """;
        List<Map<String, Object>> rows = query(sql, tableName);
        return !rows.isEmpty() && Primitives.getInt(rows.getFirst().get("n_rows")) > 0;
    }

    private PreparedStatement getPreparedStatement(String sql, Object[] params) throws SQLException {
        PreparedStatement preparedStatement = pdo.prepareStatement(sql);
        for (int i = 0; i < params.length; i++) {
            preparedStatement.setObject(i + 1, params[i]);
        }
        return preparedStatement;
    }

    /**
     * El sql de un fichero sentencia a sentencia: corta en cada ";" que no va dentro de un literal ('...', "...",
     * `...`) ni de un comentario (-- y /* *\/), que se quitan. Los ficheros de migraciones no tienen triggers (BEGIN ...
     * END con ";" dentro): no se contemplan.
     */
    private List<String> getSingleStatements(String sql) {
        List<String> singleStatements = new ArrayList<>();
        StringBuilder currentStatement = new StringBuilder();
        int i = 0;
        while (i < sql.length()) {
            char character = sql.charAt(i);
            if (character == '-' && sql.startsWith("--", i)) {
                int lineEnd = sql.indexOf('\n', i);
                i = lineEnd < 0 ? sql.length() : lineEnd;
                continue;
            }
            if (character == '/' && sql.startsWith("/*", i)) {
                int commentEnd = sql.indexOf("*/", i + 2);
                i = commentEnd < 0 ? sql.length() : commentEnd + 2;
                continue;
            }
            if (character == '\'' || character == '"' || character == '`') {
                int literalEnd = sql.indexOf(character, i + 1);
                literalEnd = literalEnd < 0 ? sql.length() - 1 : literalEnd;
                currentStatement.append(sql, i, literalEnd + 1);
                i = literalEnd + 1;
                continue;
            }
            if (character == ';') {
                addStatementIfNotBlank(singleStatements, currentStatement.toString());
                currentStatement.setLength(0);
                i++;
                continue;
            }
            currentStatement.append(character);
            i++;
        }
        addStatementIfNotBlank(singleStatements, currentStatement.toString());
        return singleStatements;
    }

    private void addStatementIfNotBlank(List<String> singleStatements, String statement) {
        if (!statement.trim().isEmpty()) {
            singleStatements.add(statement.trim());
        }
    }

    /**
     * SOLO para el log: la query con cada parametro en su sitio (strings entre comillas, como PDO::quote), para
     * copiarla de sql.sql y lanzarla tal cual. Nunca se ejecuta: la real va siempre con parametros enlazados.
     * Asume que no hay "?" dentro de literales del SQL (no los hay en los repositorios).
     * Los hashes de password (bcrypt $2y$..., argon2 $argon2...) salen como '***': no deben quedar en sql.sql.
     */
    private String getSqlForLog(String sql, Object[] params) {
        if (params.length == 0) return sql;

        List<String> paramsForLog = new ArrayList<>();
        for (Object param : params) {
            if (param == null) paramsForLog.add("NULL");
            else if (param instanceof Number) paramsForLog.add(param.toString());
            else if (!(param instanceof String string)) paramsForLog.add("'<blob>'");
            else if (PASSWORD_HASH_PATTERN.matcher(string).find()) paramsForLog.add("'***'");
            else paramsForLog.add("'" + string.replace("'", "''") + "'");
        }

        Matcher matcher = Pattern.compile("\\?").matcher(sql);
        StringBuilder sqlForLog = new StringBuilder();
        int position = 0;
        while (matcher.find()) {
            String paramForLog = position < paramsForLog.size() ? paramsForLog.get(position) : "?";
            matcher.appendReplacement(sqlForLog, Matcher.quoteReplacement(paramForLog));
            position++;
        }
        matcher.appendTail(sqlForLog);
        return sqlForLog.toString();
    }
}
