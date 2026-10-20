<?php
declare(strict_types=1);

namespace App\Modules\Shared\Infrastructure\Repositories;

use PDO;

use App\Modules\Shared\Infrastructure\Factories\DbFactory;
use App\Modules\Shared\Infrastructure\Traits\LogTrait;

abstract class AbstractSqliteRepository
{
    use LogTrait;

    protected PDO $pdo;

    public function __construct()
    {
        $this->pdo = DbFactory::getInstance()->getSqlitePdoByEnvConfiguration();
    }

    /**
     * SELECT con parametros enlazados (?, :name). Devuelve las filas como arrays asociativos.
     */
    protected function query(string $sql, array $params = []): array
    {
        $this->logSql($this->getSqlForLog($sql, $params));
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    /**
     * INSERT/UPDATE/DELETE con parametros enlazados. Devuelve las filas afectadas.
     */
    protected function execute(string $sql, array $params = []): int
    {
        $this->logSql($this->getSqlForLog($sql, $params));
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->rowCount();
    }

    /**
     * Varias sentencias de golpe (p.ej. un fichero .sql). Sin parametros: solo SQL de confianza.
     */
    protected function executeRaw(string $sql): void
    {
        $this->logSql($sql);
        $this->pdo->exec($sql);
    }

    protected function doesTableExist(string $tableName): bool
    {
        $sql = "
        -- doesTableExist
        SELECT
            COUNT(*) AS n_rows
        FROM `sqlite_master`
        WHERE 1=1
        AND type = 'table'
        AND name = ?
        ";
        $rows = $this->query($sql, [$tableName]);
        return (bool) ($rows[0]["n_rows"] ?? 0);
    }

    /**
     * SOLO para el log: la query con cada parametro en su sitio (strings con PDO::quote), para copiarla de
     * sql.sql y lanzarla tal cual. Nunca se ejecuta: la real va siempre con parametros enlazados.
     * Asume que no hay "?" dentro de literales del SQL (no los hay en los repositorios).
     * Los hashes de password (bcrypt $2y$..., argon2 $argon2...) salen como '***': no deben quedar en sql.sql.
     */
    private function getSqlForLog(string $sql, array $params): string
    {
        if (!$params) return $sql;

        $paramsForLog = array_map(
            fn(mixed $param): string => match (true) {
                $param === null => "NULL",
                is_bool($param) => $param ? "1" : "0",
                is_int($param), is_float($param) => (string) $param,
                (bool) preg_match('/^\$(2[aby]|argon2)/', (string) $param) => "'***'",
                default => (string) $this->pdo->quote((string) $param),
            },
            $params
        );

        if (!array_is_list($paramsForLog)) {
            $namedParamsForLog = [];
            foreach ($paramsForLog as $name => $paramForLog) {
                $namedParamsForLog[":".ltrim((string) $name, ":")] = $paramForLog;
            }
            return strtr($sql, $namedParamsForLog);
        }

        // closure clasica y no fn(): una arrow function captura $position por valor y no avanzaria
        $position = 0;
        return (string) preg_replace_callback(
            "/\?/",
            function () use (&$position, $paramsForLog): string {
                return $paramsForLog[$position++] ?? "?";
            },
            $sql
        );
    }

}//AbstractSqliteRepository
