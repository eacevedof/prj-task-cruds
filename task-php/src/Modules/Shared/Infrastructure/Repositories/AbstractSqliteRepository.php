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
        $this->logSql($sql);
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    /**
     * INSERT/UPDATE/DELETE con parametros enlazados. Devuelve las filas afectadas.
     */
    protected function execute(string $sql, array $params = []): int
    {
        $this->logSql($sql);
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
        SELECT COUNT(*) AS n_rows
        FROM sqlite_master
        WHERE type = 'table'
        AND name = ?
        ";
        $rows = $this->query($sql, [$tableName]);
        return (bool) ($rows[0]["n_rows"] ?? 0);
    }

}//AbstractSqliteRepository
