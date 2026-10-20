<?php
declare(strict_types=1);

namespace App\Modules\Devops\Infrastructure\Repositories;

use App\Modules\Shared\Infrastructure\Repositories\AbstractSqliteRepository;

final class RunMigrationsWriterSqliteRepository extends AbstractSqliteRepository
{
    public static function getInstance(): self
    {
        return new self();
    }

    /**
     * PDO sqlite ejecuta varias sentencias en un solo exec(): el fichero va entero, sin trocearlo por ";".
     */
    public function runSqlFile(string $pathSqlFile): void
    {
        if (!$sql = $this->getFileContentSql($pathSqlFile)) return;
        $this->executeRaw($sql);
    }

    /**
     * Fichero + registro en migrations en la MISMA transaccion: si el SQL falla no queda aplicado a
     * medias ni marcado como ejecutado. Ojo: en SQLite el DDL tambien es transaccional.
     * Sin try/catch (un repositorio no captura): si algo falla no se llega al commit, la excepcion sube
     * hasta el command y PDO deshace la transaccion abierta al cerrarse la conexion.
     */
    public function runMigrationFileAndSave(string $pathSqlFile, string $migrationFile, int $batch): void
    {
        $this->pdo->beginTransaction();
        $this->runSqlFile($pathSqlFile);
        $this->saveMigration($migrationFile, $batch);
        $this->pdo->commit();
    }

    private function saveMigration(string $migrationFile, int $batch): void
    {
        $sql = "
        -- saveMigration
        INSERT INTO `migrations` (migration_file, batch)
        VALUES (?, {$batch})
        ";
        $this->execute($sql, [$migrationFile]);
    }

    private function getFileContentSql(string $pathSqlFile): string
    {
        return trim((string) file_get_contents($pathSqlFile));
    }

}
